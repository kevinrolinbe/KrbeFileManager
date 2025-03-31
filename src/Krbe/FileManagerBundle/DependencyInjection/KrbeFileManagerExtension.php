<?php

namespace Krbe\FileManagerBundle\DependencyInjection;

use Krbe\FileManagerBundle\Service\Storage\S3Storage;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

class KrbeFileManagerExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Définition des paramètres
        $container->setParameter('krbe_file_manager', $config);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        // Configure le service de stockage en fonction du type
        if ($config['storage']['type'] === 's3') {
            $container->setAlias('krbe_file_manager.storage', 'krbe_file_manager.storage.s3');
        } else {
            $container->setAlias('krbe_file_manager.storage', 'krbe_file_manager.storage.local');
        }
    }
}
