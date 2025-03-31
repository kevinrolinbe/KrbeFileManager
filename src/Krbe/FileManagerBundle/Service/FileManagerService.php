<?php

namespace Krbe\FileManagerBundle\Service;

use Krbe\FileManagerBundle\Resolver\UploadPathResolverInterface;
use Krbe\FileManagerBundle\Resolver\QuotaResolverInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Krbe\FileManagerBundle\Exception\FileManagerException;

class FileManagerService implements FileManagerServiceInterface
{
    public function __construct(
        private StorageInterface $storageService,
        private UploadPathResolverInterface $uploadPathResolver,
        private QuotaResolverInterface $quotaResolver,
        private array $config
    ) {}

    public function createFolder(string $relativePath): ?string
    {
        // Validation du chemin
        if (!$this->isValidPath($relativePath)) {
            throw FileManagerException::createFromCode(
                FileManagerException::ERROR_INVALID_PATH
            );
        }

        // Vérification des permissions
        if (!$this->hasWritePermission($relativePath)) {
            throw FileManagerException::createFromCode(
                FileManagerException::ERROR_PERMISSION_DENIED,
                'Pas de permission d\'écriture dans le dossier'
            );
        }

        return $this->storageService->createFolder($relativePath);
    }

    /**
     * Upload un fichier.
     *
     * @param UploadedFile $file Le fichier à uploader.
     * @param string $subFolder Le sous-dossier dans lequel stocker le fichier.
     * @param bool|null $compressionEnabled Active/désactive la compression d'image.
     * @param int|null $compressionQuality Qualité de compression (0-100).
     * @param bool|null $createWebp Crée une version WebP des images.
     * @param bool|null $keepOriginal Conserve les images originales.
     *
     * @return string Le chemin du fichier uploadé.
     *
     * @throws FileManagerException Si l'upload échoue.
     */
    public function uploadFile(
        UploadedFile $file,
        string $subFolder = '',
        ?bool $compressionEnabled = null,
        ?int $compressionQuality = null,
        ?bool $createWebp = null,
        ?bool $keepOriginal = null
    ): string {
        try {
            $uploadPath = $this->uploadPathResolver->getUploadPath();
            // Validation du fichier
            $this->validateFile($file);

            // Vérification du quota
            if (!$this->quotaResolver->canAddFile($file->getSize())) {
                throw FileManagerException::createFromCode(
                    FileManagerException::ERROR_QUOTA_EXCEEDED,
                    $this->formatFileSize($this->quotaResolver->getMaxQuota())
                );
            }

            // Vérification des permissions
            if (!$this->hasWritePermission($subFolder)) {
                throw FileManagerException::createFromCode(FileManagerException::ERROR_PERMISSION_DENIED);
            }

            // Upload du fichier
            return $this->storageService->upload(
                $file,
                $subFolder,
                $compressionEnabled,
                $compressionQuality,
                $createWebp,
                $keepOriginal
            );
        } catch (\Exception $e) {
            throw FileManagerException::createFromCode(
                FileManagerException::ERROR_UPLOAD_FAILED,
                $e->getMessage()
            );
        }
    }

    public function renameFile(string $relativePath, string $newName): string
    {
        // Validation du chemin
        if (!$this->isValidPath($relativePath)) {
            throw FileManagerException::createFromCode(
                FileManagerException::ERROR_INVALID_PATH
            );
        }

        // Vérification des permissions
        if (!$this->hasWritePermission($relativePath)) {
            throw FileManagerException::createFromCode(
                FileManagerException::ERROR_PERMISSION_DENIED,
                'Pas de permission de modification pour ce fichier'
            );
        }

        try {
            return $this->storageService->rename($relativePath, $newName);
        } catch (\Exception $e) {
            throw FileManagerException::createFromCode(
                FileManagerException::ERROR_RENAME_FAILED,
                $e->getMessage()
            );
        }
    }

    public function moveFile(string $relativePath, string $destinationSubFolder): string
    {
        // Validation des chemins
        if (!$this->isValidPath($relativePath) || !$this->isValidPath($destinationSubFolder)) {
            throw FileManagerException::createFromCode(
                FileManagerException::ERROR_INVALID_PATH
            );
        }

        // Vérification des permissions
        if (!$this->hasWritePermission($relativePath) || !$this->hasWritePermission($destinationSubFolder)) {
            throw FileManagerException::createFromCode(
                FileManagerException::ERROR_PERMISSION_DENIED,
                'Permissions insuffisantes pour déplacer le fichier'
            );
        }

        try {
            return $this->storageService->move($relativePath, $destinationSubFolder);
        } catch (\Exception $e) {
            throw FileManagerException::createFromCode(
                FileManagerException::ERROR_MOVE_FAILED,
                $e->getMessage()
            );
        }
    }

    public function deleteFile(string $relativePath): bool
    {
        // Validation du chemin
        if (!$this->isValidPath($relativePath)) {
            throw FileManagerException::createFromCode(
                FileManagerException::ERROR_INVALID_PATH
            );
        }

        // Vérification des permissions
        if (!$this->hasDeletePermission($relativePath)) {
            throw FileManagerException::createFromCode(
                FileManagerException::ERROR_PERMISSION_DENIED,
                'Pas de permission de suppression pour ce fichier'
            );
        }

        // Vérification si le fichier existe
        if (!$this->fileExists($relativePath)) {
            throw FileManagerException::createFromCode(
                FileManagerException::ERROR_FILE_NOT_FOUND,
                $relativePath
            );
        }

        try {
            return $this->storageService->delete($relativePath);
        } catch (\Exception $e) {
            throw FileManagerException::createFromCode(
                FileManagerException::ERROR_DELETE_FAILED,
                $e->getMessage()
            );
        }
    }

    public function listFiles(?string $subFolder = ''): array
    {
        // Validation du chemin
        if (!$this->isValidPath($subFolder)) {
            throw FileManagerException::createFromCode(
                FileManagerException::ERROR_INVALID_PATH
            );
        }

        // Récupération des fichiers via le storage
        $files = $this->storageService->listFiles($subFolder);

        // Filtrage et formatage des résultats
        return array_map(function($file) {
            return [
                'name' => $file['name'] ?? '',
                'nameWithoutExt' => $file['isDirectory'] ? null : str_replace('.'.pathinfo($file['name'] ?? '', PATHINFO_EXTENSION), '', $file['name'] ?? ''),
                'size' => $file['size'] ?? null,
                'type' => $file['type'] ?? 'application/octet-stream',
                'lastModified' => $file['lastModified'] ?? date('Y-m-d H:i:s'),
                'isImage' => $this->isImageMimeType($file['type'] ?? ''),
                'relativePath' => $file['relativePath'] ?? '',
                'publicPath' => $file['publicPath'] ?? '',
                'isDirectory' => $file['isDirectory'] ?? false
            ];
        }, $files);
    }

    public function listDirectoryTree(string $subFolder = ''): array
    {
        // Validation du chemin
        if (!$this->isValidPath($subFolder)) {
            throw FileManagerException::createFromCode(
                FileManagerException::ERROR_INVALID_PATH
            );
        }

        return $this->storageService->listDirectoryTree($subFolder);
    }

    private function validateFile(UploadedFile $file): void
    {
        // Vérification de la taille
        if ($file->getSize() > $this->config['max_file_size']) {
            throw FileManagerException::createFromCode(
                FileManagerException::ERROR_FILE_TOO_LARGE,
                $this->formatFileSize($this->config['max_file_size'])
            );
        }

        // Vérification du type MIME
        if (!in_array($file->getMimeType(), $this->config['allowed_mime_types'])) {
            throw FileManagerException::createFromCode(
                FileManagerException::ERROR_INVALID_MIME_TYPE,
                implode(', ', $this->config['allowed_mime_types'])
            );
        }

        // Vérification des erreurs d'upload
        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw FileManagerException::createFromCode(
                FileManagerException::ERROR_UPLOAD_FAILED,
                $file->getErrorMessage()
            );
        }
    }

    private function isValidPath(string $path): bool
    {
        // Vérifie les tentatives de navigation dans l'arborescence
        if (strpos($path, '..') !== false) {
            return false;
        }

        // Nettoie le chemin
        $path = ltrim($path, '/');
        
        // Vérifie les caractères spéciaux dangereux
        if (preg_match('/[<>:"\\|?*]/', $path)) {
            return false;
        }

        // Vérifie que le chemin est relatif au dossier d'upload
        $uploadPath = $this->uploadPathResolver->getUploadPath();
        $fullPath = $uploadPath . '/' . $path;
        
        // Si le chemin existe déjà, vérifie qu'il est dans le dossier d'upload
        if (file_exists($fullPath)) {
            $realUploadPath = realpath($uploadPath);
            $realFullPath = realpath($fullPath);
            
            if ($realFullPath === false || strpos($realFullPath, $realUploadPath) !== 0) {
                return false;
            }
        }
        // Si le chemin n'existe pas encore, vérifie que le dossier parent est valide
        else {
            $parentDir = dirname($fullPath);
            if (!file_exists($parentDir)) {
                // Vérifie récursivement que le chemin parent est valide
                return $this->isValidPath(dirname($path));
            }
            
            $realParentDir = realpath($parentDir);
            $realUploadPath = realpath($uploadPath);
            
            if ($realParentDir === false || strpos($realParentDir, $realUploadPath) !== 0) {
                return false;
            }
        }

        return true;
    }

    private function hasWritePermission(string $path): bool
    {
        $uploadPath = $this->uploadPathResolver->getUploadPath();
        $targetPath = $uploadPath . '/' . ltrim($path, '/');

        // Vérifier si le dossier d'upload existe et est accessible en écriture
        if (!file_exists($uploadPath) || !is_writable($uploadPath)) {
            return false;
        }

        // Si un sous-dossier est spécifié, vérifier s'il existe et est accessible en écriture
        if (!empty($path)) {
            if (!file_exists($targetPath)) {
                // Créer le sous-dossier s'il n'existe pas
                if (!mkdir($targetPath, 0755, true)) {
                    return false;
                }
            } elseif (!is_writable($targetPath)) {
                return false;
            }
        }

        return true;
    }

    private function hasDeletePermission(string $path): bool
    {
        $fullPath = $this->uploadPathResolver->getUploadPath() . '/' . ltrim($path, '/');
        
        // Vérifie si le fichier existe
        if (!file_exists($fullPath)) {
            return false;
        }

        // Vérifie si le fichier est accessible en écriture (nécessaire pour la suppression)
        if (!is_writable($fullPath)) {
            return false;
        }

        // Vérifie si le dossier parent est accessible en écriture
        $parentDir = dirname($fullPath);
        if (!is_writable($parentDir)) {
            return false;
        }

        return true;
    }

    private function fileExists(string $path): bool
    {
        $fullPath = $this->uploadPathResolver->getUploadPath() . '/' . ltrim($path, '/');
        
        // Vérifie si le chemin est valide
        if (!$this->isValidPath($path)) {
            return false;
        }

        // Vérifie si le fichier existe et est lisible
        if (!file_exists($fullPath) || !is_readable($fullPath)) {
            return false;
        }

        // Vérifie si c'est bien un fichier (pas un dossier ou un lien symbolique)
        if (!is_file($fullPath)) {
            return false;
        }

        return true;
    }

    private function isImageMimeType(string $mimeType): bool
    {
        return strpos($mimeType, 'image/') === 0;
    }

    private function formatFileSize($size): string
    {
        // Si la taille est déjà une chaîne formatée, on la retourne telle quelle
        if (is_string($size)) {
            return $size;
        }

        // Si la taille est null, on retourne 0 B
        if ($size === null) {
            return '0 B';
        }

        // On s'assure que la taille est un entier
        $size = (int)$size;

        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        return round($size, 2) . ' ' . $units[$i];
    }
}
