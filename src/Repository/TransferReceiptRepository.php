<?php

declare(strict_types=1);

namespace WechatPayTransferBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use WechatPayTransferBundle\Entity\TransferReceipt;

/**
 * 转账电子回单仓库类
 *
 * 提供转账电子回单的数据访问操作，包括基础的CRUD操作和一些常用查询方法。
 *
 * @extends ServiceEntityRepository<TransferReceipt>
 */
#[Autoconfigure(public: true)]
#[AsRepository(entityClass: TransferReceipt::class)]
class TransferReceiptRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TransferReceipt::class);
    }

    /**
     * 保存电子回单实体
     */
    public function save(TransferReceipt $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除电子回单实体
     */
    public function remove(TransferReceipt $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 根据商户批次单号查找电子回单
     * @return array<TransferReceipt>
     */
    public function findByOutBatchNo(string $outBatchNo): array
    {
        return $this->findBy(['outBatchNo' => $outBatchNo]);
    }

    /**
     * 根据商户明细单号查找电子回单
     * @return array<TransferReceipt>
     */
    public function findByOutDetailNo(string $outDetailNo): array
    {
        return $this->findBy(['outDetailNo' => $outDetailNo]);
    }

    /**
     * 根据微信批次单号查找电子回单
     * @return array<TransferReceipt>
     */
    public function findByBatchId(string $batchId): array
    {
        return $this->findBy(['batchId' => $batchId]);
    }

    /**
     * 根据微信明细单号查找电子回单
     * @return array<TransferReceipt>
     */
    public function findByDetailId(string $detailId): array
    {
        return $this->findBy(['detailId' => $detailId]);
    }

    /**
     * 根据回单状态查找电子回单
     * @return array<TransferReceipt>
     */
    public function findByReceiptStatus(string $receiptStatus): array
    {
        return $this->findBy(['receiptStatus' => $receiptStatus]);
    }

    /**
     * 查找可下载的电子回单
     * @return array<TransferReceipt>
     */
    public function findDownloadableReceipts(): array
    {
        return $this->findBy(['receiptStatus' => 'AVAILABLE']);
    }

    /**
     * 查找需要重新申请的电子回单（已过期或生成失败）
     * @return array<TransferReceipt>
     */
    public function findReceiptsNeedingReapply(): array
    {
        /** @var array<TransferReceipt> $result */
        $result = $this->createQueryBuilder('r')
            ->where('r.receiptStatus IN (:statuses)')
            ->setParameter('statuses', ['EXPIRED', 'FAILED'])
            ->getQuery()
            ->getResult();

        return $result;
    }

    /**
     * 根据申请单号查找电子回单
     */
    public function findByApplyNo(string $applyNo): ?TransferReceipt
    {
        return $this->findOneBy(['applyNo' => $applyNo]);
    }

    /**
     * 查找最近申请的电子回单
     * @return array<TransferReceipt>
     */
    public function findRecentReceipts(int $limit = 10): array
    {
        /** @var array<TransferReceipt> $result */
        $result = $this->createQueryBuilder('r')
            ->orderBy('r.applyTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $result;
    }
}