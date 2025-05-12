<?php

namespace WechatPayTransferBundle\Tests\Repository;

use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use WechatPayTransferBundle\Repository\TransferDetailRepository;

class TransferDetailRepositoryTest extends TestCase
{
    public function testConstructor_registersCorrectEntityClass(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $repository = new TransferDetailRepository($registry);
        
        $this->assertInstanceOf(TransferDetailRepository::class, $repository);
    }
} 