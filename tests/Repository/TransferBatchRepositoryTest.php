<?php

namespace WechatPayTransferBundle\Tests\Repository;

use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use WechatPayTransferBundle\Repository\TransferBatchRepository;

class TransferBatchRepositoryTest extends TestCase
{
    public function testConstructor_registersCorrectEntityClass(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $repository = new TransferBatchRepository($registry);
        
        $this->assertInstanceOf(TransferBatchRepository::class, $repository);
    }
}
