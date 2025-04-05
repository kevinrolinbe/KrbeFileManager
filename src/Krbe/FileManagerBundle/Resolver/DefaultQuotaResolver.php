<?php

namespace Krbe\FileManagerBundle\Resolver;

/**
 * Class DefaultQuotaResolver
 * Implémentation par défaut du resolver de quota.
 */
class DefaultQuotaResolver implements QuotaResolverInterface
{
    public function __construct(
        private array $config,
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
        $totalSize = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($uploadPath, \FilesystemIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $totalSize += $file->getSize();
            }
        }
        
        return $totalSize;
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