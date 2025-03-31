<?php

namespace Krbe\FileManagerBundle\Resolver;

use Krbe\FileManagerBundle\Service\FileSystemService;

/**
 * Class DefaultQuotaResolver
 * Implémentation par défaut du resolver de quota.
 */
class DefaultQuotaResolver implements QuotaResolverInterface
{
    public function __construct(
        private array $config,
        private FileSystemService $fileSystemService,
        private UploadPathResolverInterface $uploadPathResolver
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getMaxQuota(): int
    {
        return $this->config['quota_max'] ?? -1;
    }

    /**
     * {@inheritdoc}
     */
    public function getUsedSpace(): int
    {
        $uploadPath = $this->uploadPathResolver->getUploadPath();
        return $this->fileSystemService->getDirectorySize($uploadPath);
    }

    /**
     * {@inheritdoc}
     */
    public function canAddFile(int $fileSize): bool
    {
        $maxQuota = $this->getMaxQuota();
        
        // Si le quota est illimité (-1)
        if ($maxQuota === -1) {
            return true;
        }

        $usedSpace = $this->getUsedSpace();
        return ($usedSpace + $fileSize) <= $maxQuota;
    }
} 