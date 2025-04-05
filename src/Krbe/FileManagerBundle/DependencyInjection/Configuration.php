<?php

namespace Krbe\FileManagerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('krbe_file_manager');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('required_role')
                    ->defaultValue('ROLE_FILEMANAGER')
                    ->info('%krbe_file_manager.config.required_role%')
                ->end()
                ->integerNode('max_file_size')
                    ->defaultValue(10 * 1024 * 1024) // 10MB
                    ->min(1)
                    ->info('%krbe_file_manager.config.max_file_size%')
                ->end()
                ->arrayNode('allowed_mime_types')
                    ->defaultValue([
                        'image/jpeg',
                        'image/png',
                        'image/gif',
                        'image/webp',
                        'image/svg+xml',
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'text/plain',
                        'text/csv',
                        'application/json',
                        'application/xml',
                        'text/xml'
                    ])
                    ->scalarPrototype()->end()
                    ->info('%krbe_file_manager.config.allowed_mime_types%')
                ->end()
                ->arrayNode('image_processing')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('compression_enabled')
                            ->defaultTrue()
                            ->info('%krbe_file_manager.config.image_processing.compression_enabled%')
                        ->end()
                        ->integerNode('compression_quality')
                            ->defaultValue(80)
                            ->min(0)
                            ->max(100)
                            ->info('%krbe_file_manager.config.image_processing.compression_quality%')
                        ->end()
                        ->integerNode('png_compression_level')
                            ->defaultValue(6)
                            ->min(0)
                            ->max(9)
                            ->info('%krbe_file_manager.config.image_processing.png_compression_level%')
                        ->end()
                        ->booleanNode('create_webp')
                            ->defaultTrue()
                            ->info('%krbe_file_manager.config.image_processing.create_webp%')
                        ->end()
                        ->booleanNode('keep_original')
                            ->defaultTrue()
                            ->info('%krbe_file_manager.config.image_processing.keep_original%')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('storage')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->enumNode('type')
                            ->values(['local', 's3'])
                            ->defaultValue('local')
                            ->info('%krbe_file_manager.config.storage.type%')
                        ->end()
                        ->arrayNode('local')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('path')
                                    ->defaultValue('%kernel.project_dir%/public/cdn')
                                    ->info('%krbe_file_manager.config.storage.local.path%')
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('s3')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('key')
                                    ->defaultValue(null)
                                    ->info('%krbe_file_manager.config.storage.s3.key%')
                                ->end()
                                ->scalarNode('secret')
                                    ->defaultValue(null)
                                    ->info('%krbe_file_manager.config.storage.s3.secret%')
                                ->end()
                                ->scalarNode('region')
                                    ->defaultValue(null)
                                    ->info('%krbe_file_manager.config.storage.s3.region%')
                                ->end()
                                ->scalarNode('bucket')
                                    ->defaultValue(null)
                                    ->info('%krbe_file_manager.config.storage.s3.bucket%')
                                ->end()
                                ->scalarNode('path')
                                    ->defaultValue('')
                                    ->info('%krbe_file_manager.config.storage.s3.path%')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->validate()
                ->ifTrue(function ($v) {
                    if ($v['storage']['type'] === 's3') {
                        return empty($v['storage']['s3']['bucket']) || 
                               empty($v['storage']['s3']['key']) || 
                               empty($v['storage']['s3']['secret']) || 
                               empty($v['storage']['s3']['region']);
                    }
                    return false;
                })
                ->thenInvalid('%krbe_file_manager.errors.s3_config_required%')
            ->end()
            ->validate()
                ->ifTrue(function ($v) {
                    return $v['storage']['type'] === 'local' && str_contains($v['storage']['local']['path'], '..');
                })
                ->thenInvalid('%krbe_file_manager.errors.local_path_invalid%')
            ->end()
        ;

        return $treeBuilder;
    }
}
