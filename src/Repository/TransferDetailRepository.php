<?php

namespace WechatPayTransferBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use WechatPayTransferBundle\Entity\TransferDetail;

/**
 * @method TransferDetail|null find($id, $lockMode = null, $lockVersion = null)
 * @method TransferDetail|null findOneBy(array $criteria, array $orderBy = null)
 * @method TransferDetail[]    findAll()
 * @method TransferDetail[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TransferDetailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TransferDetail::class);
    }
}
