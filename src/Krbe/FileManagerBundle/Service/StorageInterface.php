<?php

namespace Krbe\FileManagerBundle\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Interface StorageInterface
 * Définit les méthodes pour gérer le stockage des fichiers.
 */
interface StorageInterface
{
    /**
     * Crée un nouveau dossier.
     *
     * @param string $relativePath Le chemin relatif du dossier à créer
     * @return string|null Le chemin complet du dossier créé ou null en cas d'échec
     */
    public function createFolder(string $relativePath): ?string;

    /**
     * Upload un fichier avec options de traitement d'image.
     *
     * @param UploadedFile $file Le fichier à uploader
     * @param string $subFolder Le sous-dossier de destination
     * @param bool|null $compressionEnabled Active/désactive la compression d'image
     * @param int|null $compressionQuality Qualité de compression (0-100)
     * @param bool|null $createWebp Crée une version WebP de l'image
     * @param bool|null $keepOriginal Conserve l'image originale
     * @return string Le chemin relatif du fichier uploadé
     */
    public function upload(UploadedFile $file, string $subFolder = '', ?bool $compressionEnabled = null, ?int $compressionQuality = null, ?bool $createWebp = null, ?bool $keepOriginal = null): string;

    /**
     * Renomme un fichier.
     *
     * @param string $relativePath Le chemin relatif du fichier
     * @param string $newName Le nouveau nom du fichier
     * @return string Le nouveau chemin relatif du fichier
     */
    public function rename(string $relativePath, string $newName): string;

    /**
     * Déplace un fichier.
     *
     * @param string $relativePath Le chemin relatif du fichier
     * @param string $destinationSubFolder Le sous-dossier de destination
     * @return string Le nouveau chemin relatif du fichier
     */
    public function move(string $relativePath, string $destinationSubFolder): string;

    /**
     * Télécharge un fichier.
     *
     * @param string $relativePath Le chemin relatif du fichier
     * @return string|null Le contenu du fichier ou null si non trouvé
     */
    public function download(string $relativePath): ?string;

    /**
     * Supprime un fichier.
     *
     * @param string $relativePath Le chemin relatif du fichier
     * @return bool True si le fichier a été supprimé avec succès
     */
    public function delete(string $relativePath): bool;

    /**
     * Liste les fichiers d'un dossier.
     *
     * @param string|null $subFolder Le sous-dossier à lister
     * @return array Liste des fichiers avec leurs métadonnées
     */
    public function listFiles(?string $subFolder = ''): array;

    /**
     * Liste l'arborescence des dossiers.
     *
     * @param string $subFolder Le sous-dossier de départ
     * @return array L'arborescence des dossiers
     */
    public function listDirectoryTree(string $subFolder = ''): array;
}