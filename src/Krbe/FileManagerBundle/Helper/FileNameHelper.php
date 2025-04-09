<?php

namespace Krbe\FileManagerBundle\Helper;

use Krbe\FileManagerBundle\Exception\FileManagerException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Krbe\FileManagerBundle\Resolver\UploadPathResolverInterface;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Translation\DataCollectorTranslator;

/**
 * Classe FileNameHelper
 * Fournit des utilitaires pour la manipulation des noms de fichiers.
 */
class FileNameHelper
{
    public function __construct(
        private UploadPathResolverInterface $uploadPathResolver,
        private array $config,
        private Translator|DataCollectorTranslator $translator
    ) {}

    /**
     * Nettoie le nom du fichier pour qu'il soit web-safe.
     *
     * @param string $name Le nom du fichier à nettoyer
     * @return string Le nom du fichier nettoyé
     * @throws FileManagerException Si le nom est vide ou invalide
     */
    public function sanitizeFileName(string $name): string
    {
        if (empty($name)) {
            throw new \InvalidArgumentException($this->translator->trans('krbe_file_manager.errors.empty_filename'));
        }

        // Convertir en ASCII
        $name = iconv('UTF-8', 'ASCII//TRANSLIT', $name);
        if ($name === false) {
            throw new \InvalidArgumentException($this->translator->trans('krbe_file_manager.errors.ascii_conversion_failed'));
        }

        // Remplacer les caractères spéciaux par des underscores
        $name = preg_replace('/[^A-Za-z0-9\.\-_]/', '_', $name);
        
        // Limiter la longueur du nom de fichier
        $maxLength = 255;
        if (strlen($name) > $maxLength) {
            $extension = pathinfo($name, PATHINFO_EXTENSION);
            $nameWithoutExt = pathinfo($name, PATHINFO_FILENAME);
            $name = substr($nameWithoutExt, 0, $maxLength - strlen($extension) - 1) . '.' . $extension;
        }

        return $name;
    }

    /**
     * Vérifie si le type MIME du fichier est autorisé.
     *
     * @param string $mimeType Le type MIME du fichier à vérifier
     * @return bool True si le type MIME est autorisé
     */
    public function isAllowedMimeType(string $mimeType): bool
    {
        return in_array($mimeType, $this->allowedMimeTypes);
    }

    /**
     * Génère un nom de fichier unique pour un fichier uploadé.
     *
     * @param UploadedFile $file Le fichier uploadé
     * @param string $subFolder Le sous-dossier de destination
     * @return string Le nom de fichier unique
     */
    public function getUniqueFileName(UploadedFile $file, string $subFolder = ''): string
    {
        $fileName = $file->getClientOriginalName();
        $fileName = $this->sanitizeFileName($fileName);

        // Construire le chemin complet
        $folder = $this->uploadPathResolver->getUploadPath() . ($subFolder ? '/' . trim($subFolder, '/') : '');

        // Extraire le nom de base et l'extension
        $pathInfo = pathinfo($fileName);
        $extension = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';
        $baseName = $pathInfo['filename'];

        // Générer un nom unique
        $counter = 1;
        $finalName = $fileName;

        while (file_exists($folder . '/' . $finalName)) {
            $finalName = $baseName . '_' . $counter . $extension;
            $counter++;
        }

        return $finalName;
    }

    /**
     * Extrait l'extension d'un nom de fichier.
     *
     * @param string $fileName Le nom du fichier
     * @return string L'extension en minuscules
     */
    public function getExtension(string $fileName): string
    {
        return strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    }

    /**
     * Génère un nom de fichier unique dans un dossier donné.
     *
     * @param string $folder Le dossier où le fichier sera placé
     * @param string $fileName Le nom du fichier souhaité
     * @return string Le nom de fichier unique
     */
    public function getUniqueFileNameInFolder(string $folder, string $fileName): string
    {
        $sanitizedName = $this->sanitizeFileName($fileName);
        $pathInfo = pathinfo($sanitizedName);
        $extension = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';
        $baseName = $pathInfo['filename'];
        
        $counter = 1;
        $finalName = $sanitizedName;
        
        while (file_exists($folder . '/' . $finalName)) {
            $finalName = $baseName . '_' . $counter . $extension;
            $counter++;
        }
        
        return $finalName;
    }
}
