<?php

namespace WechatPayTransferBundle\Tests\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use WechatPayBundle\Entity\Merchant;
use WechatPayTransferBundle\Entity\TransferBatch;
use WechatPayTransferBundle\Entity\TransferDetail;
use WechatPayTransferBundle\Enum\TransferBatchStatus;

class TransferBatchTest extends TestCase
{
    private TransferBatch $batch;

    protected function setUp(): void
    {
        $this->batch = new TransferBatch();
    }

    public function testConstruct_initializedCollections(): void
    {
        $details = $this->batch->getDetails();
        
        $this->assertIsObject($details);
        $this->assertCount(0, $details);
    }

    public function testGetSetId_withValidId_returnsSetValue(): void
    {
        // ID字段由 Doctrine 自动生成，无法直接设置，只能通过反射设置进行测试
        $reflection = new \ReflectionClass($this->batch);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($this->batch, '123456789');

        $this->assertSame('123456789', $this->batch->getId());
    }

    public function testGetSetMerchant_withValidMerchant_returnsSetValue(): void
    {
        $merchant = $this->createMock(Merchant::class);
        
        $result = $this->batch->setMerchant($merchant);
        
        $this->assertSame($this->batch, $result);
        $this->assertSame($merchant, $this->batch->getMerchant());
    }

    public function testGetSetOutBatchNo_withValidValue_returnsSetValue(): void
    {
        $outBatchNo = 'BATCH202405010001';
        
        $result = $this->batch->setOutBatchNo($outBatchNo);
        
        $this->assertSame($this->batch, $result);
        $this->assertSame($outBatchNo, $this->batch->getOutBatchNo());
    }

    public function testGetSetBatchName_withValidValue_returnsSetValue(): void
    {
        $batchName = '5月份员工奖金';
        
        $result = $this->batch->setBatchName($batchName);
        
        $this->assertSame($this->batch, $result);
        $this->assertSame($batchName, $this->batch->getBatchName());
    }

    public function testGetSetBatchRemark_withValidValue_returnsSetValue(): void
    {
        $batchRemark = '2023年5月份员工奖金';
        
        $result = $this->batch->setBatchRemark($batchRemark);
        
        $this->assertSame($this->batch, $result);
        $this->assertSame($batchRemark, $this->batch->getBatchRemark());
    }

    public function testGetSetTotalAmount_withValidValue_returnsSetValue(): void
    {
        $totalAmount = 100000; // 单位：分
        
        $result = $this->batch->setTotalAmount($totalAmount);
        
        $this->assertSame($this->batch, $result);
        $this->assertSame($totalAmount, $this->batch->getTotalAmount());
    }

    public function testGetSetTotalNum_withValidValue_returnsSetValue(): void
    {
        $totalNum = 10;
        
        $result = $this->batch->setTotalNum($totalNum);
        
        $this->assertSame($this->batch, $result);
        $this->assertSame($totalNum, $this->batch->getTotalNum());
    }

    public function testGetSetTransferSceneId_withValidValue_returnsSetValue(): void
    {
        $transferSceneId = 'SCENE1234567890';
        
        $result = $this->batch->setTransferSceneId($transferSceneId);
        
        $this->assertSame($this->batch, $result);
        $this->assertSame($transferSceneId, $this->batch->getTransferSceneId());
    }

    public function testGetSetBatchId_withValidValue_returnsSetValue(): void
    {
        $batchId = 'wxbatch1234567890';
        
        $result = $this->batch->setBatchId($batchId);
        
        $this->assertSame($this->batch, $result);
        $this->assertSame($batchId, $this->batch->getBatchId());
    }

    public function testGetSetBatchStatus_withValidStatus_returnsSetValue(): void
    {
        $status = TransferBatchStatus::PROCESSING;
        
        $result = $this->batch->setBatchStatus($status);
        
        $this->assertSame($this->batch, $result);
        $this->assertSame($status, $this->batch->getBatchStatus());
    }

    public function testGetSetCreatedBy_withValidValue_returnsSetValue(): void
    {
        $createdBy = 'user123';
        
        $result = $this->batch->setCreatedBy($createdBy);
        
        $this->assertSame($this->batch, $result);
        $this->assertSame($createdBy, $this->batch->getCreatedBy());
    }

    public function testGetSetUpdatedBy_withValidValue_returnsSetValue(): void
    {
        $updatedBy = 'user456';
        
        $result = $this->batch->setUpdatedBy($updatedBy);
        
        $this->assertSame($this->batch, $result);
        $this->assertSame($updatedBy, $this->batch->getUpdatedBy());
    }

    public function testGetSetCreateTime_withValidDateTime_returnsSetValue(): void
    {
        $createTime = new DateTimeImmutable();
        
        $this->batch->setCreateTime($createTime);
        
        $this->assertEquals($createTime, $this->batch->getCreateTime());
    }

    public function testGetSetUpdateTime_withValidDateTime_returnsSetValue(): void
    {
        $updateTime = new DateTimeImmutable();
        
        $this->batch->setUpdateTime($updateTime);
        
        $this->assertEquals($updateTime, $this->batch->getUpdateTime());
    }

    public function testAddDetail_withNewDetail_addsToCollection(): void
    {
        $detail = new TransferDetail();
        
        $result = $this->batch->addDetail($detail);
        
        $this->assertSame($this->batch, $result);
        $this->assertTrue($this->batch->getDetails()->contains($detail));
        $this->assertSame($this->batch, $detail->getBatch());
    }

    public function testAddDetail_withAlreadyAddedDetail_doesNotDuplicate(): void
    {
        $detail = new TransferDetail();
        $this->batch->addDetail($detail);
        
        $result = $this->batch->addDetail($detail);
        
        $this->assertSame($this->batch, $result);
        $this->assertCount(1, $this->batch->getDetails());
    }

    public function testRemoveDetail_withExistingDetail_removesFromCollection(): void
    {
        $detail = new TransferDetail();
        $this->batch->addDetail($detail);
        
        $result = $this->batch->removeDetail($detail);
        
        $this->assertSame($this->batch, $result);
        $this->assertFalse($this->batch->getDetails()->contains($detail));
    }

    public function testRemoveDetail_withNonExistingDetail_doesNothing(): void
    {
        $detail = new TransferDetail();
        
        $result = $this->batch->removeDetail($detail);
        
        $this->assertSame($this->batch, $result);
        $this->assertCount(0, $this->batch->getDetails());
    }
} 