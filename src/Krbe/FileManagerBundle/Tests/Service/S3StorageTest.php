<?php

namespace Krbe\FileManagerBundle\Tests\Service;

use Krbe\FileManagerBundle\Service\Storage\S3Storage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class S3StorageTest extends TestCase
{
    private S3Storage $storage;

    protected function setUp(): void
    {
        $this->storage = new S3Storage(
            'test_key',
            'test_secret',
            'eu-west-3',
            'test-bucket',
            'test'
        );
    }

    public function testGetS3Key(): void
    {
        $file = $this->createMock(UploadedFile::class);
        $file->method('getClientOriginalName')->willReturn('test.jpg');
        
        $key = $this->storage->upload($file, 'subfolder');
        
        $this->assertEquals('test/subfolder/test.jpg', $key);
    }

    public function testGetS3KeyFromPath(): void
    {
        $path = 'test/subfolder/file.jpg';
        $key = $this->storage->getS3KeyFromPath($path);
        
        $this->assertEquals('test/test/subfolder/file.jpg', $key);
    }
} 