<?php

namespace WechatPayTransferBundle\Tests\Integration;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use WechatPayTransferBundle\WechatPayTransferBundle;

class IntegrationTestKernel extends Kernel
{
    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new DoctrineBundle(),
            new WechatPayTransferBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(function (ContainerBuilder $container) {
            $container->loadFromExtension('framework', [
                'test' => true,
                'secret' => 'test',
                'http_method_override' => false,
                'handle_all_throwables' => true,
                'validation' => [
                    'email_validation_mode' => 'html5'
                ],
                'php_errors' => [
                    'log' => true
                ],
                'uid' => [
                    'default_uuid_version' => 7,
                    'time_based_uuid_version' => 7
                ]
            ]);

            $container->loadFromExtension('doctrine', [
                'dbal' => [
                    'driver' => 'pdo_sqlite',
                    'path' => ':memory:',
                ],
                'orm' => [
                    'auto_generate_proxy_classes' => true,
                    'auto_mapping' => true,
                    'mappings' => [
                        'WechatPayTransferBundle' => [
                            'is_bundle' => true,
                            'type' => 'attribute',
                            'dir' => 'Entity',
                            'prefix' => 'WechatPayTransferBundle\Entity',
                            'alias' => 'WechatPayTransferBundle',
                        ],
                    ],
                ],
            ]);
        });
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/cache/' . spl_object_hash($this);
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/logs/' . spl_object_hash($this);
    }
} 