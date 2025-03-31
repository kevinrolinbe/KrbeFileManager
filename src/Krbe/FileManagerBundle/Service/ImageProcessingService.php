<?php

namespace Krbe\FileManagerBundle\Service;

use Krbe\FileManagerBundle\Resolver\UploadPathResolverInterface;

class ImageProcessingService
{
    private string $uploadPath;

    /**
     * Constructeur de LocalStorageService.
     *
     * @param UploadPathResolverInterface $uploadPathResolver Le resolver pour obtenir le chemin d'upload.
     * @param array $config La configuration du bundle.
     */
    public function __construct(
        private UploadPathResolverInterface $uploadPathResolver,
        private array $config
    ) {
        $this->uploadPath = $this->uploadPathResolver->getUploadPath();
    }

    /**
     * Croppe une image en utilisant les coordonnées et dimensions fournies.
     *
     * @param string $filePath Chemin complet de l'image source.
     * @param int $x Position X du coin supérieur gauche du crop.
     * @param int $y Position Y du coin supérieur gauche du crop.
     * @param int $width Largeur du crop.
     * @param int $height Hauteur du crop.
     * @param string $outputPath Chemin complet pour enregistrer l'image cropée.
     *
     * @return bool Renvoie true en cas de succès, false sinon.
     */
    public function cropImage(string $filePath, int $x, int $y, int $width, int $height, string $outputPath): bool
    {
        $filePath = $this->uploadPath . '/' . ltrim($filePath, '/');
        $outputPath = $this->uploadPath . '/' . ltrim($outputPath, '/');

        // Récupère les dimensions et le type de l'image source
        list($origWidth, $origHeight, $type) = getimagesize($filePath);
        // Crée une image vide aux dimensions du crop
        $imageCropped = imagecreatetruecolor($width, $height);
        // Crée l'image source à partir du fichier
        $imageSource = $this->createImageFromFile($filePath, $type);
        if (!$imageSource) {
            return false;
        }
        // Effectue le crop
        imagecopyresampled($imageCropped, $imageSource, 0, 0, $x, $y, $width, $height, $width, $height);
        // Enregistre l'image cropée dans le fichier de sortie
        $result = $this->saveImageToFile($imageCropped, $outputPath, $type);
        // Libère la mémoire
        imagedestroy($imageCropped);
        imagedestroy($imageSource);
        return $result;
    }

    private function createImageFromFile(string $filePath, int $type)
    {
        switch ($type) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($filePath);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($filePath);
            case IMAGETYPE_GIF:
                return imagecreatefromgif($filePath);
            case IMAGETYPE_WEBP:
                return imagecreatefromwebp($filePath);
            default:
                return null;
        }
    }

    private function saveImageToFile($image, string $outputPath, int $type): bool
    {
        switch ($type) {
            case IMAGETYPE_JPEG:
                return imagejpeg($image, $outputPath, 90);
            case IMAGETYPE_PNG:
                return imagepng($image, $outputPath);
            case IMAGETYPE_GIF:
                return imagegif($image, $outputPath);
            case IMAGETYPE_WEBP:
                return imagewebp($image, $outputPath, 90);
            default:
                return false;
        }
    }

    /**
     * Traite une image avec les options spécifiées
     * 
     * @param string $inputPath Chemin du fichier source
     * @param string $outputPath Chemin de destination
     * @param array $options Options de traitement
     * @return array Chemins des fichiers générés
     */
    public function processImage(
        string $inputPath,
        string $outputPath,
        array $options = []
    ): array {
        // Vérifier si c'est une image
        $info = getimagesize($inputPath);
        if (!$info) {
            return ['main' => $outputPath];
        }

        $mime = $info['mime'];
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/gif'])) {
            return ['main' => $outputPath];
        }

        // Options par défaut
        $options = array_merge([
            'compression_enabled' => $this->config['image_processing']['compression_enabled'],
            'compression_quality' => $this->config['image_processing']['compression_quality'],
            'create_webp' => $this->config['image_processing']['create_webp'],
            'keep_original' => $this->config['image_processing']['keep_original']
        ], $options);

        $pathInfo = pathinfo($outputPath);
        $result = ['main' => $outputPath];

        // Si on garde l'original
        if ($options['keep_original']) {
            $originalPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_original.' . $pathInfo['extension'];
            if (copy($inputPath, $originalPath)) {
                $result['original'] = $originalPath;
            }
        }

        // Optimisation du fichier principal
        if ($options['compression_enabled']) {
            $this->optimizeImage(
                $options['keep_original'] ? $originalPath : $inputPath,
                $outputPath,
                $options['compression_quality'],
                false
            );
        } else {
            // Si pas de compression, on copie simplement le fichier
            copy($inputPath, $outputPath);
        }

        // Création de la version WebP
        if ($options['create_webp']) {
            $webpPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.webp';
            if ($this->optimizeImage(
                $options['keep_original'] ? $originalPath : $inputPath,
                $webpPath,
                $options['compression_quality'],
                true
            )) {
                $result['webp'] = $webpPath;
            }
        }

        return $result;
    }

    /**
     * Optimise une image avec la qualité spécifiée
     * 
     * @param string $inputPath Chemin du fichier source
     * @param string $outputPath Chemin de destination
     * @param int|null $quality Qualité de compression (0-100)
     * @param bool $convertWebp Convertir en WebP
     * @return bool Succès de l'opération
     */
    public function optimizeImage(string $inputPath, string $outputPath, ?int $quality = null, ?bool $convertWebp = null): bool
    {
        $info = getimagesize($inputPath);
        if (!$info) {
            return false;
        }
        $mime = $info['mime'];
        switch ($mime) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($inputPath);
                break;
            case 'image/png':
                $image = imagecreatefrompng($inputPath);
                // Préserver la transparence pour les PNG
                imagealphablending($image, false);
                imagesavealpha($image, true);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($inputPath);
                break;
            default:
                return false;
        }

        // Utilisation des paramètres de configuration si non spécifiés
        $quality = $quality ?? $this->config['image_processing']['compression_quality'];
        $convertWebp = $convertWebp ?? false;

        $result = false;
        if (!$convertWebp) {
            if ($mime === 'image/jpeg') {
                $result = imagejpeg($image, $outputPath, $quality);
            } elseif ($mime === 'image/png') {
                // Utiliser le niveau de compression PNG depuis la configuration
                $pngCompressionLevel = $this->config['image_processing']['png_compression_level'];
                $result = imagepng($image, $outputPath, $pngCompressionLevel);
            } elseif ($mime === 'image/gif') {
                $result = imagegif($image, $outputPath);
            }
        } else if (function_exists('imagewebp')) {
            $result = imagewebp($image, $outputPath, $quality);
        }

        imagedestroy($image);
        return $result;
    }
}
