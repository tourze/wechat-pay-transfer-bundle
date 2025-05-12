<?php

namespace WechatPayTransferBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use WechatPayTransferBundle\DependencyInjection\WechatPayTransferExtension;
use WechatPayTransferBundle\Repository\TransferBatchRepository;
use WechatPayTransferBundle\Repository\TransferDetailRepository;

class WechatPayTransferExtensionTest extends TestCase
{
    private WechatPayTransferExtension $extension;
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->extension = new WechatPayTransferExtension();
        $this->container = new ContainerBuilder();
    }

    public function testLoad_registersRepositoryServices(): void
    {
        $this->extension->load([], $this->container);
        
        $this->assertTrue($this->container->hasDefinition(TransferBatchRepository::class));
        $this->assertTrue($this->container->hasDefinition(TransferDetailRepository::class));
    }
    
    public function testLoad_setsProperAutowireAndAutoconfigureOptions(): void
    {
        $this->extension->load([], $this->container);
        
        $batchRepoDefinition = $this->container->getDefinition(TransferBatchRepository::class);
        $detailRepoDefinition = $this->container->getDefinition(TransferDetailRepository::class);
        
        $this->assertTrue($batchRepoDefinition->isAutowired());
        $this->assertTrue($batchRepoDefinition->isAutoconfigured());
        $this->assertTrue($detailRepoDefinition->isAutowired());
        $this->assertTrue($detailRepoDefinition->isAutoconfigured());
    }
} 