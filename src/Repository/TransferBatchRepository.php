<?php

namespace WechatPayTransferBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use WechatPayTransferBundle\Entity\TransferBatch;

/**
 * @method TransferBatch|null find($id, $lockMode = null, $lockVersion = null)
 * @method TransferBatch|null findOneBy(array $criteria, array $orderBy = null)
 * @method TransferBatch[]    findAll()
 * @method TransferBatch[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TransferBatchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TransferBatch::class);
    }
}
