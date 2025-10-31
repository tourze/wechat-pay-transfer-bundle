<?php

declare(strict_types=1);

namespace WechatPayTransferBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use WechatPayTransferBundle\Entity\TransferBatch;

/**
 * @extends ServiceEntityRepository<TransferBatch>
 */
#[Autoconfigure(public: true)]
#[AsRepository(entityClass: TransferBatch::class)]
class TransferBatchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TransferBatch::class);
    }

    public function save(TransferBatch $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TransferBatch $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
