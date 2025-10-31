<?php

namespace WechatPayTransferBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use WechatPayTransferBundle\Entity\TransferBatch;
use WechatPayTransferBundle\Entity\TransferDetail;
use WechatPayTransferBundle\Enum\TransferDetailStatus;

/**
 * @internal
 */
#[CoversClass(TransferDetail::class)]
final class TransferDetailTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        // 直接创建实例
        return new TransferDetail();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'outDetailNo' => ['outDetailNo', 'DETAIL202405010001'];
        yield 'transferAmount' => ['transferAmount', 10000];
        yield 'transferRemark' => ['transferRemark', '5月奖金'];
        yield 'openid' => ['openid', 'oXYZ123456789'];
        yield 'userName' => ['userName', '张三'];
        yield 'userName_null' => ['userName', null];
        yield 'detailId' => ['detailId', 'wxdetail1234567890'];
        yield 'detailId_null' => ['detailId', null];
        yield 'detailStatus' => ['detailStatus', TransferDetailStatus::PROCESSING];
        yield 'detailStatus_null' => ['detailStatus', null];
        yield 'createdBy' => ['createdBy', 'user123'];
        yield 'updatedBy' => ['updatedBy', 'user456'];
        yield 'createTime' => ['createTime', new \DateTimeImmutable()];
        yield 'updateTime' => ['updateTime', new \DateTimeImmutable()];
    }

    public function testGetSetIdWithValidIdReturnsSetValue(): void
    {
        // ID字段由 Doctrine 自动生成，无法直接设置，只能通过反射设置进行测试
        /** @var TransferDetail $detail */
        $detail = $this->createEntity();
        $reflection = new \ReflectionClass($detail);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($detail, '123456789');

        $this->assertSame('123456789', $detail->getId());
    }

    public function testGetSetBatchWithValidBatchReturnsSetValue(): void
    {
        /** @var TransferDetail $detail */
        $detail = $this->createEntity();
        $batch = new TransferBatch();

        $detail->setBatch($batch);
        $this->assertSame($batch, $detail->getBatch());
    }

    public function testGetSetOutDetailNoWithValidValueReturnsSetValue(): void
    {
        $outDetailNo = 'DETAIL202405010001';
        /** @var TransferDetail $detail */
        $detail = $this->createEntity();

        $detail->setOutDetailNo($outDetailNo);
        $this->assertSame($outDetailNo, $detail->getOutDetailNo());
    }

    public function testGetSetTransferAmountWithValidValueReturnsSetValue(): void
    {
        $transferAmount = 10000; // 单位：分
        /** @var TransferDetail $detail */
        $detail = $this->createEntity();

        $detail->setTransferAmount($transferAmount);
        $this->assertSame($transferAmount, $detail->getTransferAmount());
    }

    public function testGetSetTransferRemarkWithValidValueReturnsSetValue(): void
    {
        $transferRemark = '5月奖金';
        /** @var TransferDetail $detail */
        $detail = $this->createEntity();

        $detail->setTransferRemark($transferRemark);
        $this->assertSame($transferRemark, $detail->getTransferRemark());
    }

    public function testGetSetOpenidWithValidValueReturnsSetValue(): void
    {
        $openid = 'oXYZ123456789';
        /** @var TransferDetail $detail */
        $detail = $this->createEntity();

        $detail->setOpenid($openid);
        $this->assertSame($openid, $detail->getOpenid());
    }

    public function testGetSetUserNameWithValidValueReturnsSetValue(): void
    {
        $userName = '张三';
        /** @var TransferDetail $detail */
        $detail = $this->createEntity();

        $detail->setUserName($userName);
        $this->assertSame($userName, $detail->getUserName());
    }

    public function testGetSetUserNameWithNullValueReturnsSetValue(): void
    {
        /** @var TransferDetail $detail */
        $detail = $this->createEntity();
        $detail->setUserName(null);
        $this->assertNull($detail->getUserName());
    }

    public function testGetSetDetailIdWithValidValueReturnsSetValue(): void
    {
        $detailId = 'wxdetail1234567890';
        /** @var TransferDetail $detail */
        $detail = $this->createEntity();

        $detail->setDetailId($detailId);
        $this->assertSame($detailId, $detail->getDetailId());
    }

    public function testGetSetDetailIdWithNullValueReturnsSetValue(): void
    {
        /** @var TransferDetail $detail */
        $detail = $this->createEntity();
        $detail->setDetailId(null);
        $this->assertNull($detail->getDetailId());
    }

    public function testGetSetDetailStatusWithValidStatusReturnsSetValue(): void
    {
        $status = TransferDetailStatus::PROCESSING;
        /** @var TransferDetail $detail */
        $detail = $this->createEntity();

        $detail->setDetailStatus($status);
        $this->assertSame($status, $detail->getDetailStatus());
    }

    public function testGetSetDetailStatusWithNullValueReturnsSetValue(): void
    {
        /** @var TransferDetail $detail */
        $detail = $this->createEntity();
        $detail->setDetailStatus(null);
        $this->assertNull($detail->getDetailStatus());
    }

    public function testGetSetCreatedByWithValidValueReturnsSetValue(): void
    {
        $createdBy = 'user123';
        /** @var TransferDetail $detail */
        $detail = $this->createEntity();

        $detail->setCreatedBy($createdBy);
        $this->assertSame($createdBy, $detail->getCreatedBy());
    }

    public function testGetSetUpdatedByWithValidValueReturnsSetValue(): void
    {
        $updatedBy = 'user456';
        /** @var TransferDetail $detail */
        $detail = $this->createEntity();

        $detail->setUpdatedBy($updatedBy);
        $this->assertSame($updatedBy, $detail->getUpdatedBy());
    }

    public function testGetSetCreateTimeWithValidDateTimeReturnsSetValue(): void
    {
        $createTime = new \DateTimeImmutable();
        /** @var TransferDetail $detail */
        $detail = $this->createEntity();

        $detail->setCreateTime($createTime);

        $this->assertEquals($createTime, $detail->getCreateTime());
    }

    public function testGetSetUpdateTimeWithValidDateTimeReturnsSetValue(): void
    {
        $updateTime = new \DateTimeImmutable();
        /** @var TransferDetail $detail */
        $detail = $this->createEntity();

        $detail->setUpdateTime($updateTime);

        $this->assertEquals($updateTime, $detail->getUpdateTime());
    }
}
