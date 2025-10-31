<?php

namespace WechatPayTransferBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use WechatPayBundle\Entity\Merchant;
use WechatPayTransferBundle\Entity\TransferBatch;
use WechatPayTransferBundle\Entity\TransferDetail;
use WechatPayTransferBundle\Enum\TransferBatchStatus;

/**
 * @internal
 */
#[CoversClass(TransferBatch::class)]
final class TransferBatchTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        // 直接创建实例，确保构造函数被调用以初始化集合
        return new TransferBatch();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'outBatchNo' => ['outBatchNo', 'BATCH202405010001'];
        yield 'batchName' => ['batchName', '5月份员工奖金'];
        yield 'batchRemark' => ['batchRemark', '2023年5月份员工奖金'];
        yield 'totalAmount' => ['totalAmount', 100000];
        yield 'totalNum' => ['totalNum', 10];
        yield 'transferSceneId' => ['transferSceneId', 'SCENE1234567890'];
        yield 'batchId' => ['batchId', 'wxbatch1234567890'];
        yield 'batchStatus' => ['batchStatus', TransferBatchStatus::PROCESSING];
        yield 'createdBy' => ['createdBy', 'user123'];
        yield 'updatedBy' => ['updatedBy', 'user456'];
        yield 'createTime' => ['createTime', new \DateTimeImmutable()];
        yield 'updateTime' => ['updateTime', new \DateTimeImmutable()];
    }

    public function testConstructInitializedCollections(): void
    {
        /** @var TransferBatch $batch */
        $batch = $this->createEntity();
        $details = $batch->getDetails();

        $this->assertCount(0, $details);
    }

    public function testGetSetIdWithValidIdReturnsSetValue(): void
    {
        // ID字段由 Doctrine 自动生成，无法直接设置，只能通过反射设置进行测试
        /** @var TransferBatch $batch */
        $batch = $this->createEntity();
        $reflection = new \ReflectionClass($batch);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($batch, '123456789');

        $this->assertSame('123456789', $batch->getId());
    }

    public function testGetSetMerchantWithValidMerchantReturnsSetValue(): void
    {
        // 使用具体类 Merchant 的 createMock 的原因：
        // 理由 1: Merchant 是核心业务实体类，设计上没有对应的接口或抽象类，为了保持数据一致性
        // 理由 2: 测试只需要验证 get/set 方法的行为，不依赖具体实现逻辑，使用 mock 可以隔离测试
        // 理由 3: 使用 mock 可以避免创建复杂的 Merchant 实例及其依赖关系（如数据库连接、配置等）
        /** @var TransferBatch $batch */
        $batch = $this->createEntity();
        $merchant = $this->createMock(Merchant::class);

        $batch->setMerchant($merchant);

        $this->assertSame($merchant, $batch->getMerchant());
    }

    public function testGetSetOutBatchNoWithValidValueReturnsSetValue(): void
    {
        $outBatchNo = 'BATCH202405010001';
        /** @var TransferBatch $batch */
        $batch = $this->createEntity();

        $batch->setOutBatchNo($outBatchNo);
        $this->assertSame($outBatchNo, $batch->getOutBatchNo());
    }

    public function testGetSetBatchNameWithValidValueReturnsSetValue(): void
    {
        $batchName = '5月份员工奖金';
        /** @var TransferBatch $batch */
        $batch = $this->createEntity();

        $batch->setBatchName($batchName);
        $this->assertSame($batchName, $batch->getBatchName());
    }

    public function testGetSetBatchRemarkWithValidValueReturnsSetValue(): void
    {
        $batchRemark = '2023年5月份员工奖金';
        /** @var TransferBatch $batch */
        $batch = $this->createEntity();

        $batch->setBatchRemark($batchRemark);
        $this->assertSame($batchRemark, $batch->getBatchRemark());
    }

    public function testGetSetTotalAmountWithValidValueReturnsSetValue(): void
    {
        $totalAmount = 100000; // 单位：分
        /** @var TransferBatch $batch */
        $batch = $this->createEntity();

        $batch->setTotalAmount($totalAmount);
        $this->assertSame($totalAmount, $batch->getTotalAmount());
    }

    public function testGetSetTotalNumWithValidValueReturnsSetValue(): void
    {
        $totalNum = 10;
        /** @var TransferBatch $batch */
        $batch = $this->createEntity();

        $batch->setTotalNum($totalNum);
        $this->assertSame($totalNum, $batch->getTotalNum());
    }

    public function testGetSetTransferSceneIdWithValidValueReturnsSetValue(): void
    {
        $transferSceneId = 'SCENE1234567890';
        /** @var TransferBatch $batch */
        $batch = $this->createEntity();

        $batch->setTransferSceneId($transferSceneId);
        $this->assertSame($transferSceneId, $batch->getTransferSceneId());
    }

    public function testGetSetBatchIdWithValidValueReturnsSetValue(): void
    {
        $batchId = 'wxbatch1234567890';
        /** @var TransferBatch $batch */
        $batch = $this->createEntity();

        $batch->setBatchId($batchId);
        $this->assertSame($batchId, $batch->getBatchId());
    }

    public function testGetSetBatchStatusWithValidStatusReturnsSetValue(): void
    {
        $status = TransferBatchStatus::PROCESSING;
        /** @var TransferBatch $batch */
        $batch = $this->createEntity();

        $batch->setBatchStatus($status);
        $this->assertSame($status, $batch->getBatchStatus());
    }

    public function testGetSetCreatedByWithValidValueReturnsSetValue(): void
    {
        $createdBy = 'user123';
        /** @var TransferBatch $batch */
        $batch = $this->createEntity();

        $batch->setCreatedBy($createdBy);
        $this->assertSame($createdBy, $batch->getCreatedBy());
    }

    public function testGetSetUpdatedByWithValidValueReturnsSetValue(): void
    {
        $updatedBy = 'user456';
        /** @var TransferBatch $batch */
        $batch = $this->createEntity();

        $batch->setUpdatedBy($updatedBy);
        $this->assertSame($updatedBy, $batch->getUpdatedBy());
    }

    public function testGetSetCreateTimeWithValidDateTimeReturnsSetValue(): void
    {
        $createTime = new \DateTimeImmutable();
        /** @var TransferBatch $batch */
        $batch = $this->createEntity();

        $batch->setCreateTime($createTime);

        $this->assertEquals($createTime, $batch->getCreateTime());
    }

    public function testGetSetUpdateTimeWithValidDateTimeReturnsSetValue(): void
    {
        $updateTime = new \DateTimeImmutable();
        /** @var TransferBatch $batch */
        $batch = $this->createEntity();

        $batch->setUpdateTime($updateTime);

        $this->assertEquals($updateTime, $batch->getUpdateTime());
    }

    public function testAddDetailWithNewDetailAddsToCollection(): void
    {
        /** @var TransferBatch $batch */
        $batch = $this->createEntity();
        $detail = new TransferDetail();

        $batch->addDetail($detail);

        $this->assertTrue($batch->getDetails()->contains($detail));
        $this->assertSame($batch, $detail->getBatch());
    }

    public function testAddDetailWithAlreadyAddedDetailDoesNotDuplicate(): void
    {
        /** @var TransferBatch $batch */
        $batch = $this->createEntity();
        $detail = new TransferDetail();
        $batch->addDetail($detail);

        $batch->addDetail($detail);

        $this->assertCount(1, $batch->getDetails());
    }

    public function testRemoveDetailWithExistingDetailRemovesFromCollection(): void
    {
        /** @var TransferBatch $batch */
        $batch = $this->createEntity();
        $detail = new TransferDetail();
        $batch->addDetail($detail);

        $batch->removeDetail($detail);

        $this->assertFalse($batch->getDetails()->contains($detail));
    }

    public function testRemoveDetailWithNonExistingDetailDoesNothing(): void
    {
        /** @var TransferBatch $batch */
        $batch = $this->createEntity();
        $detail = new TransferDetail();

        $batch->removeDetail($detail);

        $this->assertCount(0, $batch->getDetails());
    }
}
