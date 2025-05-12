<?php

namespace WechatPayTransferBundle\Tests\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use WechatPayTransferBundle\Entity\TransferBatch;
use WechatPayTransferBundle\Entity\TransferDetail;
use WechatPayTransferBundle\Enum\TransferDetailStatus;

class TransferDetailTest extends TestCase
{
    private TransferDetail $detail;

    protected function setUp(): void
    {
        $this->detail = new TransferDetail();
    }

    public function testGetSetId_withValidId_returnsSetValue(): void
    {
        // ID字段由 Doctrine 自动生成，无法直接设置，只能通过反射设置进行测试
        $reflection = new \ReflectionClass($this->detail);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($this->detail, '123456789');

        $this->assertSame('123456789', $this->detail->getId());
    }

    public function testGetSetBatch_withValidBatch_returnsSetValue(): void
    {
        $batch = new TransferBatch();
        
        $result = $this->detail->setBatch($batch);
        
        $this->assertSame($this->detail, $result);
        $this->assertSame($batch, $this->detail->getBatch());
    }

    public function testGetSetOutDetailNo_withValidValue_returnsSetValue(): void
    {
        $outDetailNo = 'DETAIL202405010001';
        
        $result = $this->detail->setOutDetailNo($outDetailNo);
        
        $this->assertSame($this->detail, $result);
        $this->assertSame($outDetailNo, $this->detail->getOutDetailNo());
    }

    public function testGetSetTransferAmount_withValidValue_returnsSetValue(): void
    {
        $transferAmount = 10000; // 单位：分
        
        $result = $this->detail->setTransferAmount($transferAmount);
        
        $this->assertSame($this->detail, $result);
        $this->assertSame($transferAmount, $this->detail->getTransferAmount());
    }

    public function testGetSetTransferRemark_withValidValue_returnsSetValue(): void
    {
        $transferRemark = '5月奖金';
        
        $result = $this->detail->setTransferRemark($transferRemark);
        
        $this->assertSame($this->detail, $result);
        $this->assertSame($transferRemark, $this->detail->getTransferRemark());
    }

    public function testGetSetOpenid_withValidValue_returnsSetValue(): void
    {
        $openid = 'oXYZ123456789';
        
        $result = $this->detail->setOpenid($openid);
        
        $this->assertSame($this->detail, $result);
        $this->assertSame($openid, $this->detail->getOpenid());
    }

    public function testGetSetUserName_withValidValue_returnsSetValue(): void
    {
        $userName = '张三';
        
        $result = $this->detail->setUserName($userName);
        
        $this->assertSame($this->detail, $result);
        $this->assertSame($userName, $this->detail->getUserName());
    }

    public function testGetSetUserName_withNullValue_returnsSetValue(): void
    {
        $result = $this->detail->setUserName(null);
        
        $this->assertSame($this->detail, $result);
        $this->assertNull($this->detail->getUserName());
    }

    public function testGetSetDetailId_withValidValue_returnsSetValue(): void
    {
        $detailId = 'wxdetail1234567890';
        
        $result = $this->detail->setDetailId($detailId);
        
        $this->assertSame($this->detail, $result);
        $this->assertSame($detailId, $this->detail->getDetailId());
    }

    public function testGetSetDetailId_withNullValue_returnsSetValue(): void
    {
        $result = $this->detail->setDetailId(null);
        
        $this->assertSame($this->detail, $result);
        $this->assertNull($this->detail->getDetailId());
    }

    public function testGetSetDetailStatus_withValidStatus_returnsSetValue(): void
    {
        $status = TransferDetailStatus::PROCESSING;
        
        $result = $this->detail->setDetailStatus($status);
        
        $this->assertSame($this->detail, $result);
        $this->assertSame($status, $this->detail->getDetailStatus());
    }

    public function testGetSetDetailStatus_withNullValue_returnsSetValue(): void
    {
        $result = $this->detail->setDetailStatus(null);
        
        $this->assertSame($this->detail, $result);
        $this->assertNull($this->detail->getDetailStatus());
    }

    public function testGetSetCreatedBy_withValidValue_returnsSetValue(): void
    {
        $createdBy = 'user123';
        
        $result = $this->detail->setCreatedBy($createdBy);
        
        $this->assertSame($this->detail, $result);
        $this->assertSame($createdBy, $this->detail->getCreatedBy());
    }

    public function testGetSetUpdatedBy_withValidValue_returnsSetValue(): void
    {
        $updatedBy = 'user456';
        
        $result = $this->detail->setUpdatedBy($updatedBy);
        
        $this->assertSame($this->detail, $result);
        $this->assertSame($updatedBy, $this->detail->getUpdatedBy());
    }

    public function testGetSetCreateTime_withValidDateTime_returnsSetValue(): void
    {
        $createTime = new DateTimeImmutable();
        
        $this->detail->setCreateTime($createTime);
        
        $this->assertEquals($createTime, $this->detail->getCreateTime());
    }

    public function testGetSetUpdateTime_withValidDateTime_returnsSetValue(): void
    {
        $updateTime = new DateTimeImmutable();
        
        $this->detail->setUpdateTime($updateTime);
        
        $this->assertEquals($updateTime, $this->detail->getUpdateTime());
    }
} 