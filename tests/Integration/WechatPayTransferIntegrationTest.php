<?php

namespace WechatPayTransferBundle\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use WechatPayTransferBundle\Repository\TransferBatchRepository;
use WechatPayTransferBundle\Repository\TransferDetailRepository;

class WechatPayTransferIntegrationTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return IntegrationTestKernel::class;
    }

    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function testServiceWiring_transferBatchRepository_isRegisteredAndWired(): void
    {
        $repository = self::getContainer()->get(TransferBatchRepository::class);
        
        $this->assertInstanceOf(TransferBatchRepository::class, $repository);
    }

    public function testServiceWiring_transferDetailRepository_isRegisteredAndWired(): void
    {
        $repository = self::getContainer()->get(TransferDetailRepository::class);
        
        $this->assertInstanceOf(TransferDetailRepository::class, $repository);
    }

    public function testServiceWiring_entityManager_isAvailable(): void
    {
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        
        $this->assertInstanceOf(EntityManagerInterface::class, $entityManager);
    }

    public function testMapping_entitiesAreRegistered(): void
    {
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        
        $entityClasses = array_map(fn($meta) => $meta->getName(), $metadata);
        
        $this->assertContains('WechatPayTransferBundle\Entity\TransferBatch', $entityClasses);
        $this->assertContains('WechatPayTransferBundle\Entity\TransferDetail', $entityClasses);
    }
} 