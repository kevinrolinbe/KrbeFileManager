<?php

namespace Krbe\FileManagerBundle\Resolver;

/**
 * Interface UploadPathResolverInterface
 * Définit les méthodes pour la résolution des chemins d'upload.
 */
interface UploadPathResolverInterface
{
    /**
     * Retourne le chemin d'upload de base.
     *
     * @return string Le chemin d'upload
     */
    public function getUploadPath(): string;

    /**
     * Retourne le chemin complet pour un fichier.
     *
     * @param string $relativePath Le chemin relatif du fichier
     * @return string Le chemin complet
     */
    public function getFullPath(string $relativePath): string;

    /**
     * Retourne le chemin relatif pour un fichier.
     *
     * @param string $fullPath Le chemin complet du fichier
     * @return string Le chemin relatif
     */
    public function getRelativePath(string $fullPath): string;
}