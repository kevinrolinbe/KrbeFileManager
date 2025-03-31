<?php

namespace Krbe\FileManagerBundle\Resolver;

/**
 * Interface QuotaResolverInterface
 * Définit les méthodes pour la gestion des quotas d'upload.
 */
interface QuotaResolverInterface
{
    /**
     * Retourne le quota maximum en octets.
     *
     * @return int Le quota maximum en octets (-1 pour illimité)
     */
    public function getMaxQuota(): int;

    /**
     * Retourne l'espace utilisé en octets.
     *
     * @return int L'espace actuellement utilisé en octets
     */
    public function getUsedSpace(): int;

    /**
     * Vérifie si l'ajout d'un fichier est possible avec le quota actuel.
     *
     * @param int $fileSize La taille du fichier à ajouter en octets
     * @return bool True si le fichier peut être ajouté, false sinon
     */
    public function canAddFile(int $fileSize): bool;
} 