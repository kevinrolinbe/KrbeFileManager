<?php

namespace Krbe\FileManagerBundle\Tests\Service;

use Krbe\FileManagerBundle\Helper\FileNameHelper;
use Krbe\FileManagerBundle\Resolver\UploadPathResolverInterface;
use Krbe\FileManagerBundle\Service\ImageProcessingService;
use Krbe\FileManagerBundle\Service\Storage\S3Storage;
use PHPUnit\Framework\TestCase;

class S3StorageTest extends TestCase
{
    public function testInstantiation(): void
    {
        $config = [
            'storage' => [
                's3' => [
                    'key'    => 'test_key',
                    'secret' => 'test_secret',
                    'region' => 'eu-west-3',
                    'bucket' => 'test-bucket',
                    'path'   => 'test',
                ],
            ],
        ];

        $storage = new S3Storage(
            '/tmp',
            $this->createMock(UploadPathResolverInterface::class),
            $this->createMock(ImageProcessingService::class),
            $this->createMock(FileNameHelper::class),
            $config
        );

        $this->assertInstanceOf(S3Storage::class, $storage);
    }
}
