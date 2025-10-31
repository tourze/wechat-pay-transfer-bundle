<?php

namespace WechatPayTransferBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use WechatPayBundle\Entity\Merchant;
use WechatPayTransferBundle\Entity\TransferBatch;
use WechatPayTransferBundle\Entity\TransferDetail;
use WechatPayTransferBundle\Repository\TransferBatchRepository;

/**
 * @internal
 * @template TEntity of TransferBatch
 * @extends AbstractRepositoryTestCase<TEntity>
 */
#[CoversClass(TransferBatchRepository::class)]
#[RunTestsInSeparateProcesses]
final class TransferBatchRepositoryTest extends AbstractRepositoryTestCase
{
    protected function onSetUp(): void
    {
        // 检查当前测试是否需要 DataFixtures 数据
        $currentTest = $this->name();
        if ('testCountWithDataFixtureShouldReturnGreaterThanZero' === $currentTest) {
            // 为 count 测试创建测试数据
            $merchant = new Merchant();
            $merchant->setMchId('test_merchant_for_count_' . uniqid());
            $merchant->setApiKey('test_api_key_1234567890abcdef1234567890abcdef');
            $merchant->setCertSerial('1234567890ABCDEF1234567890ABCDEF12345678');
            self::getEntityManager()->persist($merchant);

            // 直接创建实例，确保构造函数被调用以初始化集合
            $transferBatch = new TransferBatch();
            $transferBatch->setMerchant($merchant);
            $transferBatch->setOutBatchNo('TEST_COUNT_BATCH_' . uniqid());
            $transferBatch->setBatchName('测试计数批次');
            $transferBatch->setBatchRemark('用于测试计数的转账批次');
            $transferBatch->setTotalAmount(5000);
            $transferBatch->setTotalNum(5);
            self::getEntityManager()->persist($transferBatch);

            self::getEntityManager()->flush();
        }
    }

    protected function createNewEntity(): object
    {
        $merchant = new Merchant();
        $merchant->setMchId('test_merchant_' . uniqid());
        $merchant->setApiKey('test_api_key_1234567890abcdef');
        $merchant->setCertSerial('1234567890ABCDEF');
        self::getEntityManager()->persist($merchant);

        // 直接创建实例，确保构造函数被调用以初始化集合
        $entity = new TransferBatch();
        $entity->setMerchant($merchant);
        $entity->setOutBatchNo('TEST_BATCH_' . uniqid());
        $entity->setBatchName('测试批次');
        $entity->setBatchRemark('测试转账批次');
        $entity->setTotalAmount(1000);
        $entity->setTotalNum(1);

        return $entity;
    }

    /**
     * @return TransferBatchRepository
     */
    protected function getRepository(): TransferBatchRepository
    {
        return self::getService(TransferBatchRepository::class);
    }

    public function testConstructorRegistersCorrectEntityClass(): void
    {
        $this->assertInstanceOf(TransferBatchRepository::class, $this->getRepository());
    }

    public function testSave(): void
    {
        $merchant = new Merchant();
        $merchant->setMchId('test_merchant_' . uniqid());
        $merchant->setApiKey('test_api_key_1234567890abcdef');
        $merchant->setCertSerial('1234567890ABCDEF');
        self::getEntityManager()->persist($merchant);

        // 直接创建实例，确保构造函数被调用以初始化集合
        $transferBatch = new TransferBatch();
        $transferBatch->setMerchant($merchant);
        $transferBatch->setOutBatchNo('batch_001');
        $transferBatch->setBatchName('测试批次');
        $transferBatch->setBatchRemark('测试转账');
        $transferBatch->setTotalAmount(1000);
        $transferBatch->setTotalNum(1);

        $this->getRepository()->save($transferBatch);

        $this->assertNotNull($transferBatch->getId());
        $found = $this->getRepository()->find($transferBatch->getId());
        $this->assertSame($transferBatch, $found);
    }

    public function testRemove(): void
    {
        $merchant = new Merchant();
        $merchant->setMchId('test_merchant_' . uniqid());
        $merchant->setApiKey('test_api_key_1234567890abcdef');
        $merchant->setCertSerial('1234567890ABCDEF');
        self::getEntityManager()->persist($merchant);

        // 直接创建实例，确保构造函数被调用以初始化集合
        $transferBatch = new TransferBatch();
        $transferBatch->setMerchant($merchant);
        $transferBatch->setOutBatchNo('batch_001');
        $transferBatch->setBatchName('测试批次');
        $transferBatch->setBatchRemark('测试转账');
        $transferBatch->setTotalAmount(1000);
        $transferBatch->setTotalNum(1);

        $this->getRepository()->save($transferBatch);
        $id = $transferBatch->getId();

        $this->getRepository()->remove($transferBatch);

        $found = $this->getRepository()->find($id);
        $this->assertNull($found);
    }

    public function testFindOneByWithOrderByShouldReturnFirstMatch(): void
    {
        $merchant = new Merchant();
        $merchant->setMchId('test_merchant_' . uniqid());
        $merchant->setApiKey('test_api_key_1234567890abcdef');
        $merchant->setCertSerial('1234567890ABCDEF');
        self::getEntityManager()->persist($merchant);

        // 直接创建实例，确保构造函数被调用以初始化集合
        $transferBatch1 = new TransferBatch();
        $transferBatch1->setMerchant($merchant);
        $transferBatch1->setOutBatchNo('batch_001');
        $transferBatch1->setBatchName('B批次');
        $transferBatch1->setBatchRemark('测试转账');
        $transferBatch1->setTotalAmount(1000);
        $transferBatch1->setTotalNum(1);

        // 直接创建实例，确保构造函数被调用以初始化集合
        $transferBatch2 = new TransferBatch();
        $transferBatch2->setMerchant($merchant);
        $transferBatch2->setOutBatchNo('batch_002');
        $transferBatch2->setBatchName('A批次');
        $transferBatch2->setBatchRemark('测试转账');
        $transferBatch2->setTotalAmount(2000);
        $transferBatch2->setTotalNum(2);

        $this->getRepository()->save($transferBatch1);
        $this->getRepository()->save($transferBatch2);

        $found = $this->getRepository()->findOneBy([], ['batchName' => 'ASC']);
        $this->assertNotNull($found, 'Should find a transfer batch ordered by batch name');
        $this->assertSame('A批次', $found->getBatchName());
    }

    public function testFindByAssociationCriteria(): void
    {
        $merchant1 = new Merchant();
        $merchant1->setMchId('merchant_1');
        $merchant1->setApiKey('test_api_key_1234567890abcdef');
        $merchant1->setCertSerial('1234567890ABCDEF');
        self::getEntityManager()->persist($merchant1);

        $merchant2 = new Merchant();
        $merchant2->setMchId('merchant_2');
        $merchant2->setApiKey('test_api_key_1234567890abcdef');
        $merchant2->setCertSerial('1234567890ABCDEF');
        self::getEntityManager()->persist($merchant2);

        // 直接创建实例，确保构造函数被调用以初始化集合
        $transferBatch1 = new TransferBatch();
        $transferBatch1->setMerchant($merchant1);
        $transferBatch1->setOutBatchNo('batch_001');
        $transferBatch1->setBatchName('测试批次1');
        $transferBatch1->setBatchRemark('测试转账');
        $transferBatch1->setTotalAmount(1000);
        $transferBatch1->setTotalNum(1);

        // 直接创建实例，确保构造函数被调用以初始化集合
        $transferBatch2 = new TransferBatch();
        $transferBatch2->setMerchant($merchant2);
        $transferBatch2->setOutBatchNo('batch_002');
        $transferBatch2->setBatchName('测试批次2');
        $transferBatch2->setBatchRemark('测试转账');
        $transferBatch2->setTotalAmount(2000);
        $transferBatch2->setTotalNum(2);

        $this->getRepository()->save($transferBatch1);
        $this->getRepository()->save($transferBatch2);

        $results = $this->getRepository()->findBy(['merchant' => $merchant1]);
        $this->assertCount(1, $results);
        $this->assertSame($transferBatch1, $results[0]);
    }

    public function testCountByAssociationCriteria(): void
    {
        $merchant1 = new Merchant();
        $merchant1->setMchId('merchant_1');
        $merchant1->setApiKey('test_api_key_1234567890abcdef');
        $merchant1->setCertSerial('1234567890ABCDEF');
        self::getEntityManager()->persist($merchant1);

        $merchant2 = new Merchant();
        $merchant2->setMchId('merchant_2');
        $merchant2->setApiKey('test_api_key_1234567890abcdef');
        $merchant2->setCertSerial('1234567890ABCDEF');
        self::getEntityManager()->persist($merchant2);

        // 直接创建实例，确保构造函数被调用以初始化集合
        $transferBatch1 = new TransferBatch();
        $transferBatch1->setMerchant($merchant1);
        $transferBatch1->setOutBatchNo('batch_001');
        $transferBatch1->setBatchName('测试批次1');
        $transferBatch1->setBatchRemark('测试转账');
        $transferBatch1->setTotalAmount(1000);
        $transferBatch1->setTotalNum(1);

        // 直接创建实例，确保构造函数被调用以初始化集合
        $transferBatch2 = new TransferBatch();
        $transferBatch2->setMerchant($merchant1);
        $transferBatch2->setOutBatchNo('batch_002');
        $transferBatch2->setBatchName('测试批次2');
        $transferBatch2->setBatchRemark('测试转账');
        $transferBatch2->setTotalAmount(2000);
        $transferBatch2->setTotalNum(2);

        $this->getRepository()->save($transferBatch1);
        $this->getRepository()->save($transferBatch2);

        $count = $this->getRepository()->count(['merchant' => $merchant1]);
        $this->assertSame(2, $count);
    }

    public function testFindByCollectionAssociation(): void
    {
        $merchant = new Merchant();
        $merchant->setMchId('test_merchant_' . uniqid());
        $merchant->setApiKey('test_api_key_1234567890abcdef');
        $merchant->setCertSerial('1234567890ABCDEF');
        self::getEntityManager()->persist($merchant);

        // 直接创建实例，确保构造函数被调用以初始化集合
        $transferBatch = new TransferBatch();
        $transferBatch->setMerchant($merchant);
        $transferBatch->setOutBatchNo('batch_001');
        $transferBatch->setBatchName('测试批次');
        $transferBatch->setBatchRemark('测试转账');
        $transferBatch->setTotalAmount(1000);
        $transferBatch->setTotalNum(1);

        // 直接创建实例
        $detail = new TransferDetail();
        $detail->setBatch($transferBatch);
        $detail->setOutDetailNo('detail_001');
        $detail->setTransferAmount(1000);
        $detail->setTransferRemark('测试明细');
        $detail->setOpenid('test_openid');

        $transferBatch->addDetail($detail);
        self::getEntityManager()->persist($detail);
        $this->getRepository()->save($transferBatch);
        self::getEntityManager()->flush();

        $qb = $this->getRepository()->createQueryBuilder('tb')
            ->innerJoin('tb.details', 'd')
            ->where('d.outDetailNo = :detailNo')
            ->setParameter('detailNo', 'detail_001')
        ;

        $results = $qb->getQuery()->getResult();
        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        $this->assertArrayHasKey(0, $results);
        $this->assertSame($transferBatch, $results[0]);
    }

    public function testFindByNullableCriteria(): void
    {
        $merchant = new Merchant();
        $merchant->setMchId('test_merchant_' . uniqid());
        $merchant->setApiKey('test_api_key_1234567890abcdef');
        $merchant->setCertSerial('1234567890ABCDEF');
        self::getEntityManager()->persist($merchant);

        $uniqueNo = uniqid('test_');
        // 直接创建实例，确保构造函数被调用以初始化集合
        $transferBatch1 = new TransferBatch();
        $transferBatch1->setMerchant($merchant);
        $transferBatch1->setOutBatchNo($uniqueNo . '_001');
        $transferBatch1->setBatchName('测试批次1');
        $transferBatch1->setBatchRemark('测试转账');
        $transferBatch1->setTotalAmount(1000);
        $transferBatch1->setTotalNum(1);
        $transferBatch1->setBatchId('wx_batch_1');

        // 直接创建实例，确保构造函数被调用以初始化集合
        $transferBatch2 = new TransferBatch();
        $transferBatch2->setMerchant($merchant);
        $transferBatch2->setOutBatchNo($uniqueNo . '_002');
        $transferBatch2->setBatchName('测试批次2');
        $transferBatch2->setBatchRemark('测试转账');
        $transferBatch2->setTotalAmount(2000);
        $transferBatch2->setTotalNum(2);

        $this->getRepository()->save($transferBatch1);
        $this->getRepository()->save($transferBatch2);

        $qb = $this->getRepository()->createQueryBuilder('tb')
            ->where('tb.batchId IS NULL')
            ->andWhere('tb.outBatchNo LIKE :prefix')
            ->setParameter('prefix', $uniqueNo . '%')
        ;
        $results = $qb->getQuery()->getResult();
        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        $this->assertArrayHasKey(0, $results);
        $this->assertSame($transferBatch2, $results[0]);
    }

    public function testCountNullableCriteria(): void
    {
        $merchant = new Merchant();
        $merchant->setMchId('test_merchant_' . uniqid());
        $merchant->setApiKey('test_api_key_1234567890abcdef');
        $merchant->setCertSerial('1234567890ABCDEF');
        self::getEntityManager()->persist($merchant);

        $uniqueNo = uniqid('test_');
        // 直接创建实例，确保构造函数被调用以初始化集合
        $transferBatch1 = new TransferBatch();
        $transferBatch1->setMerchant($merchant);
        $transferBatch1->setOutBatchNo($uniqueNo . '_001');
        $transferBatch1->setBatchName('测试批次1');
        $transferBatch1->setBatchRemark('测试转账');
        $transferBatch1->setTotalAmount(1000);
        $transferBatch1->setTotalNum(1);
        $transferBatch1->setTransferSceneId('scene_1');

        // 直接创建实例，确保构造函数被调用以初始化集合
        $transferBatch2 = new TransferBatch();
        $transferBatch2->setMerchant($merchant);
        $transferBatch2->setOutBatchNo($uniqueNo . '_002');
        $transferBatch2->setBatchName('测试批次2');
        $transferBatch2->setBatchRemark('测试转账');
        $transferBatch2->setTotalAmount(2000);
        $transferBatch2->setTotalNum(2);

        // 直接创建实例，确保构造函数被调用以初始化集合
        $transferBatch3 = new TransferBatch();
        $transferBatch3->setMerchant($merchant);
        $transferBatch3->setOutBatchNo($uniqueNo . '_003');
        $transferBatch3->setBatchName('测试批次3');
        $transferBatch3->setBatchRemark('测试转账');
        $transferBatch3->setTotalAmount(3000);
        $transferBatch3->setTotalNum(3);

        $this->getRepository()->save($transferBatch1);
        $this->getRepository()->save($transferBatch2);
        $this->getRepository()->save($transferBatch3);

        $qb = $this->getRepository()->createQueryBuilder('tb')
            ->select('COUNT(tb.id)')
            ->where('tb.transferSceneId IS NULL')
            ->andWhere('tb.outBatchNo LIKE :prefix')
            ->setParameter('prefix', $uniqueNo . '%')
        ;
        $count = $qb->getQuery()->getSingleScalarResult();
        $this->assertSame(2, $count);
    }

    public function testFindOneByWithOrderByScenario(): void
    {
        $merchant = new Merchant();
        $merchant->setMchId('test_merchant_' . uniqid());
        $merchant->setApiKey('test_api_key_1234567890abcdef');
        $merchant->setCertSerial('1234567890ABCDEF');
        self::getEntityManager()->persist($merchant);

        // 直接创建实例，确保构造函数被调用以初始化集合
        $transferBatch1 = new TransferBatch();
        $transferBatch1->setMerchant($merchant);
        $transferBatch1->setOutBatchNo('batch_001');
        $transferBatch1->setBatchName('测试批次1');
        $transferBatch1->setBatchRemark('测试转账');
        $transferBatch1->setTotalAmount(2000);
        $transferBatch1->setTotalNum(2);

        // 直接创建实例，确保构造函数被调用以初始化集合
        $transferBatch2 = new TransferBatch();
        $transferBatch2->setMerchant($merchant);
        $transferBatch2->setOutBatchNo('batch_002');
        $transferBatch2->setBatchName('测试批次2');
        $transferBatch2->setBatchRemark('测试转账');
        $transferBatch2->setTotalAmount(1000);
        $transferBatch2->setTotalNum(1);

        $this->getRepository()->save($transferBatch1);
        $this->getRepository()->save($transferBatch2);

        $found = $this->getRepository()->findOneBy(['merchant' => $merchant], ['totalAmount' => 'ASC']);
        $this->assertSame($transferBatch2, $found);
        $this->assertSame(1000, $found->getTotalAmount());
    }

    public function testFindByIsNullCriteria(): void
    {
        $merchant = new Merchant();
        $merchant->setMchId('test_merchant_' . uniqid());
        $merchant->setApiKey('test_api_key_1234567890abcdef');
        $merchant->setCertSerial('1234567890ABCDEF');
        self::getEntityManager()->persist($merchant);

        // 直接创建实例，确保构造函数被调用以初始化集合
        $transferBatch1 = new TransferBatch();
        $transferBatch1->setMerchant($merchant);
        $transferBatch1->setOutBatchNo('batch_001');
        $transferBatch1->setBatchName('测试批次1');
        $transferBatch1->setBatchRemark('测试转账');
        $transferBatch1->setTotalAmount(1000);
        $transferBatch1->setTotalNum(1);
        $transferBatch1->setBatchStatus(null);

        // 直接创建实例，确保构造函数被调用以初始化集合
        $transferBatch2 = new TransferBatch();
        $transferBatch2->setMerchant($merchant);
        $transferBatch2->setOutBatchNo('batch_002');
        $transferBatch2->setBatchName('测试批次2');
        $transferBatch2->setBatchRemark('测试转账');
        $transferBatch2->setTotalAmount(2000);
        $transferBatch2->setTotalNum(2);
        $transferBatch2->setBatchStatus(null);

        $this->getRepository()->save($transferBatch1);
        $this->getRepository()->save($transferBatch2);

        $qb = $this->getRepository()->createQueryBuilder('tb')
            ->where('tb.batchStatus IS NULL')
        ;
        $results = $qb->getQuery()->getResult();
        $this->assertIsArray($results);
        $this->assertCount(2, $results);
    }

    public function testCountIsNullCriteria(): void
    {
        $merchant = new Merchant();
        $merchant->setMchId('test_merchant_' . uniqid());
        $merchant->setApiKey('test_api_key_1234567890abcdef');
        $merchant->setCertSerial('1234567890ABCDEF');
        self::getEntityManager()->persist($merchant);

        $uniqueNo = uniqid('test_');
        // 直接创建实例，确保构造函数被调用以初始化集合
        $transferBatch1 = new TransferBatch();
        $transferBatch1->setMerchant($merchant);
        $transferBatch1->setOutBatchNo($uniqueNo . '_001');
        $transferBatch1->setBatchName('测试批次1');
        $transferBatch1->setBatchRemark('测试转账');
        $transferBatch1->setTotalAmount(1000);
        $transferBatch1->setTotalNum(1);
        $transferBatch1->setBatchId(null);

        // 直接创建实例，确保构造函数被调用以初始化集合
        $transferBatch2 = new TransferBatch();
        $transferBatch2->setMerchant($merchant);
        $transferBatch2->setOutBatchNo($uniqueNo . '_002');
        $transferBatch2->setBatchName('测试批次2');
        $transferBatch2->setBatchRemark('测试转账');
        $transferBatch2->setTotalAmount(2000);
        $transferBatch2->setTotalNum(2);
        $transferBatch2->setBatchId('wx_batch_123');

        $this->getRepository()->save($transferBatch1);
        $this->getRepository()->save($transferBatch2);

        $qb = $this->getRepository()->createQueryBuilder('tb')
            ->select('COUNT(tb.id)')
            ->where('tb.batchId IS NULL')
            ->andWhere('tb.outBatchNo LIKE :prefix')
            ->setParameter('prefix', $uniqueNo . '%')
        ;
        $count = $qb->getQuery()->getSingleScalarResult();
        $this->assertSame(1, $count);
    }
}
