<?php

namespace Krbe\FileManagerBundle\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

interface FileManagerServiceInterface
{
    /**
     * Crée un nouveau dossier.
     */
    public function createFolder(string $relativePath): ?string;

    /**
     * Upload un fichier avec options de traitement.
     */
    public function uploadFile(
        UploadedFile $file,
        string $subFolder = '',
        ?bool $compressionEnabled = null,
        ?int $compressionQuality = null,
        ?bool $createWebp = null,
        ?bool $keepOriginal = null
    ): string;

    /**
     * Renomme un fichier.
     */
    public function renameFile(string $relativePath, string $newName): string;

    /**
     * Déplace un fichier vers un nouveau dossier.
     */
    public function moveFile(string $relativePath, string $destinationSubFolder): string;

    /**
     * Supprime un fichier.
     */
    public function deleteFile(string $relativePath): bool;

    /**
     * Liste les fichiers d'un dossier.
     */
    public function listFiles(?string $subFolder = ''): array;

    /**
     * Liste l'arborescence des dossiers.
     */
    public function listDirectoryTree(string $subFolder = ''): array;
} 