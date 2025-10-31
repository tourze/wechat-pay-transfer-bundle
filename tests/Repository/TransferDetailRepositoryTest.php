<?php

namespace WechatPayTransferBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use WechatPayBundle\Entity\Merchant;
use WechatPayTransferBundle\Entity\TransferBatch;
use WechatPayTransferBundle\Entity\TransferDetail;
use WechatPayTransferBundle\Repository\TransferDetailRepository;

/**
 * @internal
 * @template TEntity of TransferDetail
 * @extends AbstractRepositoryTestCase<TEntity>
 */
#[CoversClass(TransferDetailRepository::class)]
#[RunTestsInSeparateProcesses]
final class TransferDetailRepositoryTest extends AbstractRepositoryTestCase
{
    protected function onSetUp(): void
    {
        // 检查当前测试是否需要 DataFixtures 数据
        $currentTest = $this->name();
        if ('testCountWithDataFixtureShouldReturnGreaterThanZero' === $currentTest) {
            // 为 count 测试创建测试数据
            $merchant = $this->createTestMerchant();
            $transferBatch = $this->createTestBatch($merchant, 'TEST_COUNT_BATCH_' . uniqid(), 5000, 5);
            $transferBatch->setBatchName('测试计数批次');
            $transferBatch->setBatchRemark('用于测试计数的转账批次');

            $transferDetail = $this->createTestDetail($transferBatch, 'TEST_COUNT_DETAIL_' . uniqid(), 1000, 'test_openid_for_count_' . uniqid());
            $transferDetail->setTransferRemark('测试计数明细');

            $this->getRepository()->save($transferDetail);
        }
    }

    protected function createNewEntity(): object
    {
        $merchant = $this->createTestMerchant();
        $batch = $this->createTestBatch($merchant, 'TEST_BATCH_' . uniqid(), 1000, 1);
        $batch->setBatchName('测试批次');
        $batch->setBatchRemark('测试转账批次');

        $entity = $this->createTestDetail($batch, 'TEST_DETAIL_' . uniqid(), 1000, 'test_openid_' . uniqid());
        $entity->setTransferRemark('测试转账明细');

        return $entity;
    }

    /**
     * @return TransferDetailRepository
     */
    protected function getRepository(): TransferDetailRepository
    {
        return self::getService(TransferDetailRepository::class);
    }

    protected function createTestMerchant(): Merchant
    {
        $merchant = new Merchant();
        $merchant->setMchId('test_merchant_' . uniqid());
        $merchant->setApiKey('test_api_key_1234567890abcdef');
        $merchant->setCertSerial('1234567890ABCDEF');
        self::getEntityManager()->persist($merchant);

        return $merchant;
    }

    protected function createTestBatch(Merchant $merchant, string $outBatchNo, int $totalAmount = 1000, int $totalNum = 1): TransferBatch
    {
        // 直接创建实例，确保构造函数被调用以初始化集合
        $batch = new TransferBatch();
        $batch->setMerchant($merchant);
        $batch->setOutBatchNo($outBatchNo);
        $batch->setBatchName('测试批次');
        $batch->setBatchRemark('测试转账');
        $batch->setTotalAmount($totalAmount);
        $batch->setTotalNum($totalNum);
        self::getEntityManager()->persist($batch);

        return $batch;
    }

    protected function createTestDetail(TransferBatch $batch, string $outDetailNo, int $transferAmount = 1000, ?string $openid = null, ?string $userName = null): TransferDetail
    {
        // 直接创建实例
        $detail = new TransferDetail();
        $detail->setBatch($batch);
        $detail->setOutDetailNo($outDetailNo);
        $detail->setTransferAmount($transferAmount);
        $detail->setTransferRemark('测试明细');
        $detail->setOpenid($openid ?? 'test_openid_' . uniqid());
        if (null !== $userName) {
            $detail->setUserName($userName);
        }

        return $detail;
    }

    public function testConstructorRegistersCorrectEntityClass(): void
    {
        $this->assertInstanceOf(TransferDetailRepository::class, $this->getRepository());
    }

    public function testSave(): void
    {
        $merchant = $this->createTestMerchant();
        $batch = $this->createTestBatch($merchant, 'batch_001');
        $transferDetail = $this->createTestDetail($batch, 'detail_001', 1000, 'test_openid');

        $this->getRepository()->save($transferDetail);

        $this->assertNotNull($transferDetail->getId());
        $found = $this->getRepository()->find($transferDetail->getId());
        $this->assertSame($transferDetail, $found);
    }

    public function testRemove(): void
    {
        $merchant = $this->createTestMerchant();
        $batch = $this->createTestBatch($merchant, 'batch_001');
        $transferDetail = $this->createTestDetail($batch, 'detail_001', 1000, 'test_openid');

        $this->getRepository()->save($transferDetail);
        $id = $transferDetail->getId();

        $this->getRepository()->remove($transferDetail);

        $found = $this->getRepository()->find($id);
        $this->assertNull($found);
    }

    public function testFindOneByWithOrderByShouldReturnFirstMatch(): void
    {
        $merchant = $this->createTestMerchant();
        $batch = $this->createTestBatch($merchant, 'batch_001', 3000, 2);

        $transferDetail1 = $this->createTestDetail($batch, 'detail_002', 2000, 'test_openid_2');
        $transferDetail1->setTransferRemark('测试明细B');

        $transferDetail2 = $this->createTestDetail($batch, 'detail_001', 1000, 'test_openid_1');
        $transferDetail2->setTransferRemark('测试明细A');

        $this->getRepository()->save($transferDetail1);
        $this->getRepository()->save($transferDetail2);

        $found = $this->getRepository()->findOneBy(['batch' => $batch], ['transferRemark' => 'ASC']);
        $this->assertNotNull($found, 'Should find a transfer detail ordered by transfer remark');
        $this->assertSame('测试明细A', $found->getTransferRemark());
    }

    public function testFindByAssociationCriteria(): void
    {
        $merchant = $this->createTestMerchant();
        $batch1 = $this->createTestBatch($merchant, 'batch_001');
        $batch1->setBatchName('测试批次1');

        $batch2 = $this->createTestBatch($merchant, 'batch_002');
        $batch2->setBatchName('测试批次2');

        $transferDetail1 = $this->createTestDetail($batch1, 'detail_001', 1000, 'test_openid_1');
        $transferDetail1->setTransferRemark('测试明细1');

        $transferDetail2 = $this->createTestDetail($batch2, 'detail_002', 1000, 'test_openid_2');
        $transferDetail2->setTransferRemark('测试明细2');

        $this->getRepository()->save($transferDetail1);
        $this->getRepository()->save($transferDetail2);

        $results = $this->getRepository()->findBy(['batch' => $batch1]);
        $this->assertCount(1, $results);
        $this->assertSame($transferDetail1, $results[0]);
    }

    public function testCountByAssociationCriteria(): void
    {
        $merchant = $this->createTestMerchant();
        $batch = $this->createTestBatch($merchant, 'batch_001', 2000, 2);

        $transferDetail1 = $this->createTestDetail($batch, 'detail_001', 1000, 'test_openid_1');
        $transferDetail1->setTransferRemark('测试明细1');

        $transferDetail2 = $this->createTestDetail($batch, 'detail_002', 1000, 'test_openid_2');
        $transferDetail2->setTransferRemark('测试明细2');

        $this->getRepository()->save($transferDetail1);
        $this->getRepository()->save($transferDetail2);

        $count = $this->getRepository()->count(['batch' => $batch]);
        $this->assertSame(2, $count);
    }

    public function testFindByNullableCriteria(): void
    {
        $merchant = $this->createTestMerchant();
        $batch = $this->createTestBatch($merchant, 'batch_001', 2000, 2);

        $transferDetail1 = $this->createTestDetail($batch, 'detail_001', 1000, 'test_openid_1', '张三');
        $transferDetail1->setTransferRemark('测试明细1');

        $transferDetail2 = $this->createTestDetail($batch, 'detail_002', 1000, 'test_openid_2');
        $transferDetail2->setTransferRemark('测试明细2');

        $this->getRepository()->save($transferDetail1);
        $this->getRepository()->save($transferDetail2);

        $qb = $this->getRepository()->createQueryBuilder('td')
            ->where('td.userName IS NULL')
            ->andWhere('td.batch = :batch')
            ->setParameter('batch', $batch)
        ;
        $results = $qb->getQuery()->getResult();
        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        $this->assertArrayHasKey(0, $results);
        $this->assertSame($transferDetail2, $results[0]);
    }

    public function testCountNullableCriteria(): void
    {
        $merchant = $this->createTestMerchant();
        $batch = $this->createTestBatch($merchant, 'batch_001', 3000, 3);

        $transferDetail1 = $this->createTestDetail($batch, 'detail_001', 1000, 'test_openid_1');
        $transferDetail1->setTransferRemark('测试明细1');
        $transferDetail1->setDetailId('wx_detail_1');

        $transferDetail2 = $this->createTestDetail($batch, 'detail_002', 1000, 'test_openid_2');
        $transferDetail2->setTransferRemark('测试明细2');

        $transferDetail3 = $this->createTestDetail($batch, 'detail_003', 1000, 'test_openid_3');
        $transferDetail3->setTransferRemark('测试明细3');

        $this->getRepository()->save($transferDetail1);
        $this->getRepository()->save($transferDetail2);
        $this->getRepository()->save($transferDetail3);

        $qb = $this->getRepository()->createQueryBuilder('td')
            ->select('COUNT(td.id)')
            ->where('td.detailId IS NULL')
            ->andWhere('td.batch = :batch')
            ->setParameter('batch', $batch)
        ;
        $count = $qb->getQuery()->getSingleScalarResult();
        $this->assertSame(2, $count);
    }

    public function testFindOneByWithOrderByScenario(): void
    {
        $merchant = $this->createTestMerchant();
        $batch = $this->createTestBatch($merchant, 'batch_001', 3000, 2);

        $transferDetail1 = $this->createTestDetail($batch, 'detail_001', 2000, 'test_openid_1');
        $transferDetail1->setTransferRemark('测试明细1');

        $transferDetail2 = $this->createTestDetail($batch, 'detail_002', 1000, 'test_openid_2');
        $transferDetail2->setTransferRemark('测试明细2');

        $this->getRepository()->save($transferDetail1);
        $this->getRepository()->save($transferDetail2);

        $found = $this->getRepository()->findOneBy(['batch' => $batch], ['transferAmount' => 'ASC']);
        $this->assertSame($transferDetail2, $found);
        $this->assertSame(1000, $found->getTransferAmount());
    }

    public function testFindByIsNullCriteria(): void
    {
        $merchant = $this->createTestMerchant();
        $batch = $this->createTestBatch($merchant, 'batch_001', 2000, 2);

        $transferDetail1 = $this->createTestDetail($batch, 'detail_001', 1000, 'test_openid_1');
        $transferDetail1->setTransferRemark('测试明细1');
        $transferDetail1->setDetailStatus(null);

        $transferDetail2 = $this->createTestDetail($batch, 'detail_002', 1000, 'test_openid_2');
        $transferDetail2->setTransferRemark('测试明细2');
        $transferDetail2->setDetailStatus(null);

        $this->getRepository()->save($transferDetail1);
        $this->getRepository()->save($transferDetail2);

        $qb = $this->getRepository()->createQueryBuilder('td')
            ->where('td.detailStatus IS NULL')
        ;
        $results = $qb->getQuery()->getResult();
        $this->assertIsArray($results);
        $this->assertCount(2, $results);
    }

    public function testCountIsNullCriteria(): void
    {
        $merchant = $this->createTestMerchant();
        $batch = $this->createTestBatch($merchant, 'batch_001', 2000, 2);

        $transferDetail1 = $this->createTestDetail($batch, 'detail_001', 1000, 'test_openid_1');
        $transferDetail1->setTransferRemark('测试明细1');
        $transferDetail1->setUserName(null);

        $transferDetail2 = $this->createTestDetail($batch, 'detail_002', 1000, 'test_openid_2', '张三');
        $transferDetail2->setTransferRemark('测试明细2');

        $this->getRepository()->save($transferDetail1);
        $this->getRepository()->save($transferDetail2);

        $qb = $this->getRepository()->createQueryBuilder('td')
            ->select('COUNT(td.id)')
            ->where('td.userName IS NULL')
            ->andWhere('td.batch = :batch')
            ->setParameter('batch', $batch)
        ;
        $count = $qb->getQuery()->getSingleScalarResult();
        $this->assertSame(1, $count);
    }
}
