<?php

namespace Krbe\FileManagerBundle\Service\Storage;

use Krbe\FileManagerBundle\Helper\FileNameHelper;
use Krbe\FileManagerBundle\Resolver\UploadPathResolverInterface;
use Krbe\FileManagerBundle\Service\StorageInterface;
use Krbe\FileManagerBundle\Service\ImageProcessingService;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Krbe\FileManagerBundle\Exception\FileManagerException;

class LocalStorage implements StorageInterface
{
    private string $uploadPath;

    public function __construct(
        private string $kernelProjectDir,
        private UploadPathResolverInterface $uploadPathResolver,
        private ImageProcessingService $imageProcessingService,
        private FileNameHelper $fileNameHelper,
        private array $config
    ) {
        $this->uploadPath = $this->uploadPathResolver->getUploadPath();
    }

    public function createFolder(string $relativePath): ?string
    {
        $newFolderPath = rtrim($this->uploadPath . $relativePath);

        if (file_exists($newFolderPath)) {
            return null;
        }

        if (!mkdir($newFolderPath, 0777, true)) {
            return null;
        }

        return $newFolderPath;
    }

    /**
     * Upload un fichier vers le stockage local.
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
    public function upload(
        UploadedFile $file,
        string $subFolder = '',
        ?bool $compressionEnabled = null,
        ?int $compressionQuality = null,
        ?bool $createWebp = null,
        ?bool $keepOriginal = null
    ): string {
        // Construire le chemin du dossier
        $folder = '/' . trim($this->uploadPath, '/') . ($subFolder ? '/' . trim($subFolder, '/') : '');

        // Créer le dossier s'il n'existe pas
        if (!is_dir($folder)) {
            if (!mkdir($folder, 0777, true)) {
                throw new FileException('Impossible de créer le dossier de destination');
            }
        }

        // Obtenir un nom unique dans le dossier
        $finalName = $this->fileNameHelper->getUniqueFileName($file, $subFolder);

        // Chemin complet du fichier
        $filePath = $folder . '/' . $finalName;

        // Déplacer le fichier
        try {
            $file->move($folder, $finalName);

            // Si c'est une image et que la compression est activée
            if (($compressionEnabled ?? $this->config['image_processing']['compression_enabled']) && 
                strpos($file->getClientMimeType(), 'image/') === 0) {

                // Traitement de l'image avec toutes les options
                $this->imageProcessingService->processImage(
                    $filePath,
                    $filePath,
                    [
                        'compression_enabled' => $compressionEnabled ?? $this->config['image_processing']['compression_enabled'],
                        'compression_quality' => $compressionQuality ?? $this->config['image_processing']['compression_quality'],
                        'create_webp' => $createWebp ?? $this->config['image_processing']['create_webp'],
                        'keep_original' => $keepOriginal ?? $this->config['image_processing']['keep_original']
                    ]
                );
            }
        } catch (FileException $e) {
            throw $e;
        }

        // Construire le chemin relatif pour le retour
        $relativePath = str_replace($this->uploadPath . '/', '', $filePath);
        $relativePath = ltrim($relativePath, '/');

        return $relativePath;
    }

    /**
     * Télécharge un fichier depuis le stockage local.
     *
     * @param string $relativePath Le chemin relatif du fichier
     * @return string|null Le contenu du fichier ou null si non trouvé
     */
    public function download(string $relativePath): ?string
    {
        $filePath = $this->uploadPath . '/' . ltrim($relativePath, '/');
        return file_exists($filePath) ? file_get_contents($filePath) : null;
    }

    public function delete(string $relativePath): bool
    {
        $fullPath = $this->uploadPath . '/' . trim($relativePath, '/');

        if (!file_exists($fullPath)) {
            throw new FileManagerException('Le fichier n\'existe pas.');
        }

        if (is_dir($fullPath)) {
            return $this->deleteDirectory($fullPath);
        }

        return unlink($fullPath);
    }

    public function rename(string $relativePath, string $newName): string
    {
        $currentPath = $this->uploadPath . '/' . ltrim($relativePath, '/');
        
        // Extraire le dossier et le sous-dossier
        $folder = dirname($currentPath);
        $subFolder = trim(str_replace($this->uploadPath, '', $folder), '/');
        
        // Créer un UploadedFile temporaire pour utiliser getUniqueFileName
        $tempFile = new UploadedFile(
            $currentPath,
            $newName,
            mime_content_type($currentPath),
            null,
            true
        );
        
        // Obtenir un nom unique
        $finalName = $this->fileNameHelper->getUniqueFileName($tempFile, $subFolder);
        
        // Construire le nouveau chemin
        $newPath = $folder . '/' . $finalName;
        
        // Vérifier si le fichier source existe
        if (!file_exists($currentPath)) {
            throw new FileException('Le fichier source n\'existe pas');
        }
        
        // Vérifier si le fichier de destination n'existe pas déjà
        if (file_exists($newPath) && $currentPath !== $newPath) {
            throw new FileException('Un fichier avec ce nom existe déjà');
        }
        
        // Renommer le fichier
        $result = rename($currentPath, $newPath);
        if (!$result) {
            throw new FileException('Impossible de renommer le fichier');
        }
        
        return ltrim(str_replace($this->uploadPath, '', $newPath), '/');
    }

    public function move(string $relativePath, string $destinationSubFolder): string
    {
        $fullSourcePath = $this->uploadPath . '/' . trim($relativePath, '/');
        $destinationFolder = $this->uploadPath . '/' . trim($destinationSubFolder, '/');
        $filename = basename($fullSourcePath);
        $fullDestinationPath = $destinationFolder . '/' . $filename;

        if (!file_exists($fullSourcePath)) {
            throw new FileManagerException('Le fichier source n\'existe pas.');
        }

        if (!is_dir($destinationFolder)) {
            if (!mkdir($destinationFolder, 0777, true)) {
                throw new FileManagerException('Impossible de créer le dossier de destination.');
            }
        }

        if (file_exists($fullDestinationPath)) {
            throw new FileManagerException('Un fichier avec ce nom existe déjà à la destination.');
        }

        if (!rename($fullSourcePath, $fullDestinationPath)) {
            throw new FileManagerException('Erreur lors du déplacement du fichier.');
        }

        return str_replace($this->uploadPath . '/', '', $fullDestinationPath);
    }

    public function listFiles(?string $subFolder = ''): array
    {
        $folder = $this->uploadPath . ($subFolder ? '/' . trim($subFolder, '/') : '');

        if (!is_dir($folder)) {
            return [];
        }

        $files = [];
        foreach (scandir($folder) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $folder . '/' . $entry;
            $isDirectory = is_dir($path);
            $relativePath = ($subFolder ? trim($subFolder, '/') . '/' : '') . $entry;
            $files[] = [
                'name' => $entry,
                'nameWithoutExt' => $isDirectory ? null : str_replace('.'.pathinfo($entry, PATHINFO_EXTENSION), '', $entry),
                'isDirectory' => $isDirectory,
                'size' => $isDirectory ? null : $this->formatBytes(filesize($path)),
                'type' => $isDirectory ? null : $this->getSimplifiedFileType($path),
                'lastModified' => date("Y-m-d H:i:s", filemtime($path)),
                'extension' => $isDirectory ? null : pathinfo($entry, PATHINFO_EXTENSION),
                'relativePath' => $relativePath,
                'publicPath' => str_replace($this->kernelProjectDir . '/public', '', $path),
            ];
        }

        usort($files, function($a, $b) {
            if ($a['isDirectory'] === $b['isDirectory']) {
                return 0;
            }
            return $a['isDirectory'] ? -1 : 1;
        });

        return $files;
    }

    public function listDirectoryTree(string $subFolder = ''): array
    {
        $folder = '/' . trim($this->uploadPath, '/') . ($subFolder ? '/' . trim($subFolder, '/') : '');

        if (!is_dir($folder)) {
            return [];
        }

        $tree = [];
        foreach (scandir($folder) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $currentPath = $folder . '/' . $entry;
            if (!is_dir($currentPath)) {
                continue;
            }
            $relativePath = ($subFolder ? trim($subFolder, '/') . '/' : '') . $entry;
            $node = [
                'name' => $entry,
                'path' => $relativePath,
                'type' => 'dir'
            ];

            // Récursivement obtenir les sous-dossiers
            $children = $this->listDirectoryTree($relativePath);
            if (!empty($children)) {
                $node['children'] = $children;
            }

            $tree[] = $node;
        }

        return $tree;
    }

    private function getSimplifiedFileType(string $path): string
    {
        $mimeType = mime_content_type($path);
        if (strpos($mimeType, 'image/') === 0) {
            return 'image';
        }
        if (strpos($mimeType, 'video/') === 0) {
            return 'video';
        }
        if (strpos($mimeType, 'audio/') === 0) {
            return 'audio';
        }
        if (strpos($mimeType, 'application/pdf') === 0) {
            return 'pdf';
        }
        return 'file';
    }

    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    public function getUploadPath(): string
    {
        return $this->uploadPath;
    }

    private function deleteDirectory(string $dir): bool
    {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($dir);
    }
} 