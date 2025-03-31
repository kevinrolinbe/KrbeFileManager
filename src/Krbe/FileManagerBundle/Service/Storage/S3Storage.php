<?php

namespace Krbe\FileManagerBundle\Service\Storage;

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Krbe\FileManagerBundle\Service\StorageInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Krbe\FileManagerBundle\Exception\FileManagerException;
use Krbe\FileManagerBundle\Resolver\UploadPathResolverInterface;
use Krbe\FileManagerBundle\Service\ImageProcessingService;
use Krbe\FileManagerBundle\Helper\FileNameHelper;

class S3Storage implements StorageInterface
{
    private S3Client $s3Client;
    private string $bucket;
    private string $basePath;

    public function __construct(
        private string $kernelProjectDir,
        private UploadPathResolverInterface $uploadPathResolver,
        private ImageProcessingService $imageProcessingService,
        private FileNameHelper $fileNameHelper,
        private array $config
    ) {
        $this->bucket = $this->config['storage']['s3']['bucket'];
        $this->basePath = $this->config['storage']['s3']['path'];

        $this->s3Client = new S3Client([
            'version' => 'latest',
            'region'  => $this->config['storage']['s3']['region'],
            'credentials' => [
                'key'    => $this->config['storage']['s3']['key'],
                'secret' => $this->config['storage']['s3']['secret'],
            ],
            'endpoint' => 'https://s3.' . $this->config['storage']['s3']['region'] . '.amazonaws.com',
        ]);
    }

    /**
     * Upload un fichier vers le stockage S3.
     *
     * @param UploadedFile $file Le fichier à uploader.
     * @param string $subFolder Le sous-dossier dans lequel stocker le fichier.
     * @param bool|null $compressionEnabled Active/désactive la compression d'image.
     * @param int|null $compressionQuality Qualité de compression (0-100).
     * @param bool|null $createWebp Crée une version WebP des images.
     * @param bool|null $keepOriginal Conserve les images originales.
     *
     * @return string Le chemin du fichier uploadé.
     *
     * @throws FileManagerException Si l'upload échoue.
     */
    public function upload(
        UploadedFile $file,
        string $subFolder = '',
        ?bool $compressionEnabled = null,
        ?int $compressionQuality = null,
        ?bool $createWebp = null,
        ?bool $keepOriginal = null
    ): string {
        try {
            // Validation du fichier
            $this->validateFile($file);

            // Génération du nom de fichier unique
            $fileName = $this->fileNameHelper->getUniqueFileName($file, $subFolder);

            // Chemin complet dans le bucket
            $path = $this->config['storage']['s3']['path'] . '/' . $fileName;

            // Upload du fichier vers S3
            $this->s3Client->putObject([
                'Bucket' => $this->config['storage']['s3']['bucket'],
                'Key' => $path,
                'Body' => fopen($file->getPathname(), 'r'),
                'ContentType' => $file->getMimeType(),
                'ACL' => 'public-read'
            ]);

            return $fileName;
        } catch (\Exception $e) {
            throw FileManagerException::createFromCode(
                FileManagerException::ERROR_UPLOAD_FAILED,
                ['error' => $e->getMessage()]
            );
        }
    }

    public function delete(string $relativePath): bool
    {
        try {
            $key = $this->getS3KeyFromPath($relativePath);
            
            $this->s3Client->deleteObject([
                'Bucket' => $this->bucket,
                'Key'    => $key,
            ]);

            return true;
        } catch (S3Exception $e) {
            throw new FileException('Failed to delete file from S3: ' . $e->getMessage());
        }
    }

    public function rename(string $relativePath, string $newName): string
    {
        try {
            $oldKey = $this->getS3KeyFromPath($relativePath);
            $newKey = $this->getS3KeyFromPath($newName);

            $this->s3Client->copyObject([
                'Bucket'     => $this->bucket,
                'CopySource' => $this->bucket . '/' . $oldKey,
                'Key'        => $newKey,
            ]);

            $this->s3Client->deleteObject([
                'Bucket' => $this->bucket,
                'Key'    => $oldKey,
            ]);

            return $newKey;
        } catch (S3Exception $e) {
            throw new FileException('Failed to rename file in S3: ' . $e->getMessage());
        }
    }

    public function move(string $relativePath, string $destinationSubFolder): string
    {
        try {
            $oldKey = $this->getS3KeyFromPath($relativePath);
            $newKey = $this->getS3KeyFromPath($destinationSubFolder . '/' . basename($relativePath));

            $this->s3Client->copyObject([
                'Bucket'     => $this->bucket,
                'CopySource' => $this->bucket . '/' . $oldKey,
                'Key'        => $newKey,
            ]);

            $this->s3Client->deleteObject([
                'Bucket' => $this->bucket,
                'Key'    => $oldKey,
            ]);

            return $newKey;
        } catch (S3Exception $e) {
            throw new FileException('Failed to move file in S3: ' . $e->getMessage());
        }
    }

    public function listFiles(?string $subFolder = ''): array
    {
        try {
            $prefix = $this->getS3KeyFromPath($subFolder);
            
            $result = $this->s3Client->listObjects([
                'Bucket' => $this->bucket,
                'Prefix' => $prefix,
            ]);

            $files = [];
            foreach ($result['Contents'] as $object) {
                if ($object['Key'] === $prefix) {
                    continue; // Skip the folder itself
                }

                $files[] = [
                    'name' => basename($object['Key']),
                    'size' => (int) $object['Size'],
                    'type' => $this->getMimeType($object['Key']),
                    'lastModified' => $object['LastModified']->format('Y-m-d H:i:s'),
                ];
            }

            return $files;
        } catch (S3Exception $e) {
            throw new FileException('Failed to list files from S3: ' . $e->getMessage());
        }
    }

    public function listDirectoryTree(string $subFolder = ''): array
    {
        try {
            $prefix = $this->getS3KeyFromPath($subFolder);
            
            $result = $this->s3Client->listObjects([
                'Bucket' => $this->bucket,
                'Prefix' => $prefix,
                'Delimiter' => '/',
            ]);

            $tree = [];
            
            // Add folders
            if (isset($result['CommonPrefixes'])) {
                foreach ($result['CommonPrefixes'] as $prefix) {
                    $path = $prefix['Prefix'];
                    $name = basename(rtrim($path, '/'));
                    $tree[] = [
                        'name' => $name,
                        'path' => $path,
                        'type' => 'dir',
                    ];
                }
            }

            // Add files
            if (isset($result['Contents'])) {
                foreach ($result['Contents'] as $object) {
                    if ($object['Key'] === $prefix) {
                        continue; // Skip the folder itself
                    }

                    $tree[] = [
                        'name' => basename($object['Key']),
                        'path' => $object['Key'],
                        'type' => 'file',
                        'size' => $object['Size'],
                        'lastModified' => $object['LastModified']->format('Y-m-d H:i:s'),
                    ];
                }
            }

            return $tree;
        } catch (S3Exception $e) {
            throw new FileException('Failed to list directory tree from S3: ' . $e->getMessage());
        }
    }

    private function getS3Key(UploadedFile $file, string $subFolder): string
    {
        $fileName = $file->getClientOriginalName();
        $path = $subFolder ? $subFolder . '/' . $fileName : $fileName;
        return $this->basePath . '/' . $path;
    }

    private function getS3KeyFromPath(string $path): string
    {
        return $this->basePath . '/' . ltrim($path, '/');
    }

    private function getMimeType(string $key): string
    {
        try {
            $result = $this->s3Client->headObject([
                'Bucket' => $this->bucket,
                'Key'    => $key,
            ]);

            return $result['ContentType'] ?? 'application/octet-stream';
        } catch (S3Exception $e) {
            return 'application/octet-stream';
        }
    }

    /**
     * Crée un dossier dans le stockage S3.
     *
     * @param string $relativePath Le chemin relatif du dossier à créer.
     * @return string|null Le chemin du dossier créé ou null en cas d'échec.
     */
    public function createFolder(string $relativePath): ?string
    {
        try {
            $key = $this->getS3KeyFromPath($relativePath);
            
            // Dans S3, les dossiers sont simulés avec des objets vides se terminant par '/'
            $this->s3Client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $key . '/',
                'Body' => '',
                'ContentType' => 'application/x-directory'
            ]);

            return $key;
        } catch (S3Exception $e) {
            return null;
        }
    }

    /**
     * Télécharge un fichier depuis le stockage S3.
     *
     * @param string $relativePath Le chemin relatif du fichier à télécharger.
     * @return string|null Le contenu du fichier ou null si le fichier n'existe pas.
     */
    public function download(string $relativePath): ?string
    {
        try {
            $key = $this->getS3KeyFromPath($relativePath);
            
            $result = $this->s3Client->getObject([
                'Bucket' => $this->bucket,
                'Key' => $key
            ]);

            return $result['Body']->getContents();
        } catch (S3Exception $e) {
            return null;
        }
    }
} 