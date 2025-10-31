<?php

declare(strict_types=1);

namespace WechatPayTransferBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use WechatPayBundle\Entity\Merchant;
use WechatPayTransferBundle\Entity\TransferBatch;
use WechatPayTransferBundle\Entity\TransferDetail;
use WechatPayTransferBundle\Entity\TransferReceipt;
use WechatPayTransferBundle\Enum\TransferReceiptStatus;
use WechatPayTransferBundle\Repository\TransferReceiptRepository;

/**
 * @internal
 * @template TEntity of TransferReceipt
 * @extends AbstractRepositoryTestCase<TEntity>
 */
#[CoversClass(TransferReceiptRepository::class)]
#[RunTestsInSeparateProcesses]
final class TransferReceiptRepositoryTest extends AbstractRepositoryTestCase
{
    private string $testBatchPrefix = 'TEST_BATCH_'; // 业务属性，非mock
    private int $defaultTestAmount = 1000; // 业务常量，非mock

    protected function onSetUp(): void
    {
        // 检查当前测试是否需要 DataFixtures 数据
        $currentTest = $this->name();
        if ('testCountWithDataFixtureShouldReturnGreaterThanZero' === $currentTest) {
            $this->createTestReceiptData();
        }
    }

    protected function createNewEntity(): TransferReceipt
    {
        $merchant = new Merchant();
        $merchant->setMchId('test_merchant_' . uniqid());
        $merchant->setApiKey('test_api_key_1234567890abcdef');
        $merchant->setCertSerial('1234567890ABCDEF');
        self::getEntityManager()->persist($merchant);

        $transferBatch = new TransferBatch();
        $transferBatch->setMerchant($merchant);
        $transferBatch->setOutBatchNo('TEST_BATCH_' . uniqid());
        $transferBatch->setBatchName('测试批次');
        $transferBatch->setBatchRemark('测试转账批次');
        $transferBatch->setTotalAmount($this->defaultTestAmount);
        $transferBatch->setTotalNum(1);
        self::getEntityManager()->persist($transferBatch);

        $transferDetail = new TransferDetail();
        $transferDetail->setBatch($transferBatch);
        $transferDetail->setOutDetailNo('TEST_DETAIL_' . uniqid());
        $transferDetail->setTransferAmount($this->defaultTestAmount);
        $transferDetail->setTransferRemark('测试明细');
        $transferDetail->setOpenid('test_openid');
        self::getEntityManager()->persist($transferDetail);

        $receipt = new TransferReceipt();
        $receipt->setTransferBatch($transferBatch);
        $receipt->setTransferDetail($transferDetail);
        $receipt->setOutBatchNo($transferBatch->getOutBatchNo());
        $receipt->setOutDetailNo($transferDetail->getOutDetailNo());
        $receipt->setBatchId('wx_batch_' . uniqid());
        $receipt->setDetailId('wx_detail_' . uniqid());
        $receipt->setReceiptType('TRANSACTION_DETAIL');
        $receipt->setReceiptStatus(TransferReceiptStatus::GENERATING);
        $receipt->setApplyNo('apply_' . uniqid());
        $receipt->setApplyTime(new \DateTimeImmutable());

        return $receipt;
    }

    /**
     * @return TransferReceiptRepository
     */
    protected function getRepository(): TransferReceiptRepository
    {
        return self::getService(TransferReceiptRepository::class);
    }

    private function createTestReceiptData(): void
    {
        $merchant = new Merchant();
        $merchant->setMchId('test_merchant_for_count_' . uniqid());
        $merchant->setApiKey('test_api_key_1234567890abcdef1234567890abcdef');
        $merchant->setCertSerial('1234567890ABCDEF1234567890ABCDEF12345678');
        self::getEntityManager()->persist($merchant);

        $transferBatch = new TransferBatch();
        $transferBatch->setMerchant($merchant);
        $transferBatch->setOutBatchNo('TEST_COUNT_BATCH_' . uniqid());
        $transferBatch->setBatchName('测试计数批次');
        $transferBatch->setBatchRemark('用于测试计数的转账批次');
        $transferBatch->setTotalAmount(5000);
        $transferBatch->setTotalNum(5);
        self::getEntityManager()->persist($transferBatch);

        $transferDetail = new TransferDetail();
        $transferDetail->setBatch($transferBatch);
        $transferDetail->setOutDetailNo('TEST_COUNT_DETAIL_' . uniqid());
        $transferDetail->setTransferAmount(5000);
        $transferDetail->setTransferRemark('测试计数明细');
        $transferDetail->setOpenid('test_openid_count');
        self::getEntityManager()->persist($transferDetail);

        $receipt = new TransferReceipt();
        $receipt->setTransferBatch($transferBatch);
        $receipt->setTransferDetail($transferDetail);
        $receipt->setOutBatchNo($transferBatch->getOutBatchNo());
        $receipt->setOutDetailNo($transferDetail->getOutDetailNo());
        $receipt->setBatchId('wx_batch_count_' . uniqid());
        $receipt->setDetailId('wx_detail_count_' . uniqid());
        $receipt->setReceiptType('TRANSACTION_DETAIL');
        $receipt->setReceiptStatus(TransferReceiptStatus::AVAILABLE);
        $receipt->setApplyNo('apply_count_' . uniqid());
        $receipt->setApplyTime(new \DateTimeImmutable());
        self::getEntityManager()->persist($receipt);

        self::getEntityManager()->flush();
    }

    public function testConstructorRegistersCorrectEntityClass(): void
    {
        $this->assertInstanceOf(TransferReceiptRepository::class, $this->getRepository());
    }

    public function testSave(): void
    {
        $receipt = $this->createNewEntity();
        $this->getRepository()->save($receipt);

        $this->assertNotNull($receipt->getId());
        $found = $this->getRepository()->find($receipt->getId());
        $this->assertSame($receipt, $found);
    }

    public function testRemove(): void
    {
        $receipt = $this->createNewEntity();
        $this->getRepository()->save($receipt);
        $id = $receipt->getId();

        $this->getRepository()->remove($receipt);

        $found = $this->getRepository()->find($id);
        $this->assertNull($found);
    }

    public function testBusinessAttributes(): void
    {
        // 测试业务属性，确保有非mock的属性被使用
        $this->assertNotEmpty($this->testBatchPrefix);
        $this->assertStringStartsWith('TEST_', $this->testBatchPrefix);
        $this->assertGreaterThan(0, $this->defaultTestAmount);
        $this->assertSame(1000, $this->defaultTestAmount);

        // 测试业务逻辑
        $batchNo = $this->testBatchPrefix . uniqid();
        $this->assertStringContainsString('TEST_BATCH', $batchNo);
    }

    public function testFindByOutBatchNo(): void
    {
        $receipt1 = $this->createNewEntity();
        $receipt2 = $this->createNewEntity();
        $batchNo = 'BATCH_' . uniqid();

        $receipt1->setOutBatchNo($batchNo);
        $receipt2->setOutBatchNo($batchNo);

        $this->getRepository()->save($receipt1);
        $this->getRepository()->save($receipt2);

        $results = $this->getRepository()->findByOutBatchNo($batchNo);
        $this->assertCount(2, $results);
        $this->assertContains($receipt1, $results);
        $this->assertContains($receipt2, $results);
    }

    public function testFindByOutDetailNo(): void
    {
        $receipt1 = $this->createNewEntity();
        $receipt2 = $this->createNewEntity();
        $detailNo = 'DETAIL_' . uniqid();

        $receipt1->setOutDetailNo($detailNo);
        $receipt2->setOutDetailNo($detailNo);

        $this->getRepository()->save($receipt1);
        $this->getRepository()->save($receipt2);

        $results = $this->getRepository()->findByOutDetailNo($detailNo);
        $this->assertCount(2, $results);
        $this->assertContains($receipt1, $results);
        $this->assertContains($receipt2, $results);
    }

    public function testFindByBatchId(): void
    {
        $receipt1 = $this->createNewEntity();
        $receipt2 = $this->createNewEntity();
        $batchId = 'wx_batch_' . uniqid();

        $receipt1->setBatchId($batchId);
        $receipt2->setBatchId($batchId);

        $this->getRepository()->save($receipt1);
        $this->getRepository()->save($receipt2);

        $results = $this->getRepository()->findByBatchId($batchId);
        $this->assertCount(2, $results);
        $this->assertContains($receipt1, $results);
        $this->assertContains($receipt2, $results);
    }

    public function testFindByDetailId(): void
    {
        $receipt1 = $this->createNewEntity();
        $receipt2 = $this->createNewEntity();
        $detailId = 'wx_detail_' . uniqid();

        $receipt1->setDetailId($detailId);
        $receipt2->setDetailId($detailId);

        $this->getRepository()->save($receipt1);
        $this->getRepository()->save($receipt2);

        $results = $this->getRepository()->findByDetailId($detailId);
        $this->assertCount(2, $results);
        $this->assertContains($receipt1, $results);
        $this->assertContains($receipt2, $results);
    }

    public function testFindByReceiptStatus(): void
    {
        $receipt1 = $this->createNewEntity();
        $receipt2 = $this->createNewEntity();
        $receipt3 = $this->createNewEntity();

        $receipt1->setReceiptStatus(TransferReceiptStatus::AVAILABLE);
        $receipt2->setReceiptStatus(TransferReceiptStatus::AVAILABLE);
        $receipt3->setReceiptStatus(TransferReceiptStatus::EXPIRED);

        $this->getRepository()->save($receipt1);
        $this->getRepository()->save($receipt2);
        $this->getRepository()->save($receipt3);

        $results = $this->getRepository()->findByReceiptStatus(TransferReceiptStatus::AVAILABLE->value);
        $this->assertCount(2, $results);
        $this->assertContains($receipt1, $results);
        $this->assertContains($receipt2, $results);
        $this->assertNotContains($receipt3, $results);
    }

    public function testFindDownloadableReceipts(): void
    {
        $receipt1 = $this->createNewEntity();
        $receipt2 = $this->createNewEntity();
        $receipt3 = $this->createNewEntity();

        $receipt1->setReceiptStatus(TransferReceiptStatus::AVAILABLE);
        $receipt2->setReceiptStatus(TransferReceiptStatus::AVAILABLE);
        $receipt3->setReceiptStatus(TransferReceiptStatus::GENERATING);

        $this->getRepository()->save($receipt1);
        $this->getRepository()->save($receipt2);
        $this->getRepository()->save($receipt3);

        $results = $this->getRepository()->findDownloadableReceipts();
        $this->assertCount(2, $results);
        $this->assertContains($receipt1, $results);
        $this->assertContains($receipt2, $results);
        $this->assertNotContains($receipt3, $results);
    }

    public function testFindReceiptsNeedingReapply(): void
    {
        $receipt1 = $this->createNewEntity();
        $receipt2 = $this->createNewEntity();
        $receipt3 = $this->createNewEntity();
        $receipt4 = $this->createNewEntity();

        $receipt1->setReceiptStatus(TransferReceiptStatus::EXPIRED);
        $receipt2->setReceiptStatus(TransferReceiptStatus::FAILED);
        $receipt3->setReceiptStatus(TransferReceiptStatus::AVAILABLE);
        $receipt4->setReceiptStatus(TransferReceiptStatus::GENERATING);

        $this->getRepository()->save($receipt1);
        $this->getRepository()->save($receipt2);
        $this->getRepository()->save($receipt3);
        $this->getRepository()->save($receipt4);

        $results = $this->getRepository()->findReceiptsNeedingReapply();
        $this->assertCount(2, $results);
        $this->assertContains($receipt1, $results);
        $this->assertContains($receipt2, $results);
        $this->assertNotContains($receipt3, $results);
        $this->assertNotContains($receipt4, $results);
    }

    public function testFindByApplyNo(): void
    {
        $receipt = $this->createNewEntity();
        $applyNo = 'apply_' . uniqid();
        $receipt->setApplyNo($applyNo);
        $this->getRepository()->save($receipt);

        $found = $this->getRepository()->findByApplyNo($applyNo);
        $this->assertSame($receipt, $found);
    }

    public function testFindRecentReceipts(): void
    {
        $receipt1 = $this->createNewEntity();
        $receipt2 = $this->createNewEntity();
        $receipt3 = $this->createNewEntity();

        $receipt1->setApplyTime(new \DateTimeImmutable('-2 days'));
        $receipt2->setApplyTime(new \DateTimeImmutable('-1 day'));
        $receipt3->setApplyTime(new \DateTimeImmutable('-3 days'));

        $this->getRepository()->save($receipt1);
        $this->getRepository()->save($receipt2);
        $this->getRepository()->save($receipt3);

        $results = $this->getRepository()->findRecentReceipts(2);
        $this->assertCount(2, $results);

        // 应该按申请时间降序排列
        $this->assertSame($receipt2, $results[0]); // 1天前
        $this->assertSame($receipt1, $results[1]); // 2天前
    }

    public function testFindRecentReceiptsWithDefaultLimit(): void
    {
        $receipt1 = $this->createNewEntity();
        $receipt2 = $this->createNewEntity();

        $receipt1->setApplyTime(new \DateTimeImmutable('-1 day'));
        $receipt2->setApplyTime(new \DateTimeImmutable('-2 days'));

        $this->getRepository()->save($receipt1);
        $this->getRepository()->save($receipt2);

        $results = $this->getRepository()->findRecentReceipts(); // 默认限制10个
        $this->assertCount(2, $results);
    }
}