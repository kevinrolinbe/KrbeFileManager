<?php

namespace Krbe\FileManagerBundle\Resolver;

use Krbe\FileManagerBundle\Exception\FileManagerException;

/**
 * Class DefaultUploadPathResolver
 * Implémentation par défaut du resolver de chemin d'upload.
 */
class DefaultUploadPathResolver implements UploadPathResolverInterface
{
    private string $uploadPath;

    public function __construct(
        private string $kernelProjectDir,
        private array $config
    ) {
        $this->uploadPath = $this->kernelProjectDir . '/public/cdn/' . $this->config['upload_folder'];
    }

    /**
     * Retourne le chemin d'upload de base.
     *
     * @return string Le chemin d'upload
     */
    public function getUploadPath(): string
    {
        return $this->uploadPath;
    }

    /**
     * Retourne le chemin complet pour un fichier.
     *
     * @param string $relativePath Le chemin relatif du fichier
     * @return string Le chemin complet
     */
    public function getFullPath(string $relativePath): string
    {
        return $this->uploadPath . '/' . ltrim($relativePath, '/');
    }

    /**
     * Retourne le chemin relatif pour un fichier.
     *
     * @param string $fullPath Le chemin complet du fichier
     * @return string Le chemin relatif
     */
    public function getRelativePath(string $fullPath): string
    {
        return str_replace($this->uploadPath, '', $fullPath);
    }

    public function getRelativeUploadPath(): string
    {
        if ($this->config['storage']['type'] !== 'local') {
            throw new \RuntimeException('Le stockage local est requis pour cette opération');
        }

        $path = $this->config['storage']['local']['path'];
        
        // Si le chemin commence par public/, on le retourne tel quel
        if (str_starts_with($path, 'public/')) {
            return substr($path, 7); // Enlève le préfixe 'public/'
        }
        
        return $path;
    }
}
