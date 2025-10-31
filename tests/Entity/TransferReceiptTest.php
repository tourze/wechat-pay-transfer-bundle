<?php

declare(strict_types=1);

namespace WechatPayTransferBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use WechatPayTransferBundle\Entity\TransferBatch;
use WechatPayTransferBundle\Entity\TransferDetail;
use WechatPayTransferBundle\Entity\TransferReceipt;
use WechatPayTransferBundle\Enum\TransferReceiptStatus;
use WechatPayBundle\Entity\Merchant;

/**
 * 转账电子回单实体测试
 *
 * 测试TransferReceipt实体的各种功能，包括数据设置、关联关系和状态管理。
 *
 * @see https://pay.weixin.qq.com/doc/v3/merchant/4012716452
 */
#[CoversClass(TransferReceipt::class)]
final class TransferReceiptTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new TransferReceipt();
    }

    /**
     * @return iterable<array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield ['outBatchNo', 'test_batch_001'];
        yield ['outDetailNo', 'test_detail_001'];
        yield ['batchId', 'wx_batch_001'];
        yield ['detailId', 'wx_detail_001'];
        yield ['receiptType', 'TRANSACTION_DETAIL'];
        yield ['receiptStatus', TransferReceiptStatus::GENERATING];
        yield ['downloadUrl', 'https://example.com/receipt.pdf'];
        yield ['hashValue', 'abcdef123456'];
        yield ['generateTime', new \DateTimeImmutable('2024-01-01 10:00:00')];
        yield ['expireTime', new \DateTimeImmutable('2024-01-01 18:00:00')];
        yield ['fileName', 'receipt.pdf'];
        yield ['fileSize', 1024];
        yield ['rawResponse', '{"status": "success"}'];
        yield ['applyNo', 'apply_001'];
        yield ['applyTime', new \DateTimeImmutable('2024-01-01 09:00:00')];
    }

    private function createTestMerchant(): Merchant
    {
        $merchant = new Merchant();
        $merchant->setMchId('test_mch_id');
        $merchant->setApiKey('test_api_key');
        $merchant->setCertSerial('test_cert_serial');
        return $merchant;
    }

    private function createTestBatch(): TransferBatch
    {
        $merchant = $this->createTestMerchant();
        $batch = new TransferBatch();
        $batch->setMerchant($merchant);
        $batch->setOutBatchNo('test_batch_001');
        $batch->setBatchName('Test Batch');
        $batch->setBatchRemark('Test Batch Remark');
        $batch->setTotalAmount(10000);
        $batch->setTotalNum(1);
        return $batch;
    }

    private function createTestDetail(): TransferDetail
    {
        $batch = $this->createTestBatch();
        $detail = new TransferDetail();
        $detail->setBatch($batch);
        $detail->setOutDetailNo('test_detail_001');
        $detail->setTransferAmount(10000);
        $detail->setTransferRemark('Test Transfer');
        $detail->setOpenid('test_openid');
        $detail->setUserName('Test User');
        return $detail;
    }

    public function testSetAndGetTransferBatch(): void
    {
        $transferReceipt = new TransferReceipt();
        $transferBatch = $this->createTestBatch();

        $transferReceipt->setTransferBatch($transferBatch);
        $this->assertSame($transferBatch, $transferReceipt->getTransferBatch());
    }

    public function testSetAndGetTransferDetail(): void
    {
        $transferReceipt = new TransferReceipt();
        $transferDetail = $this->createTestDetail();

        $transferReceipt->setTransferDetail($transferDetail);
        $this->assertSame($transferDetail, $transferReceipt->getTransferDetail());
    }

    public function testSetAndGetOutBatchNo(): void
    {
        $transferReceipt = new TransferReceipt();
        $outBatchNo = 'test_batch_001';

        $transferReceipt->setOutBatchNo($outBatchNo);
        $this->assertEquals($outBatchNo, $transferReceipt->getOutBatchNo());
    }

    public function testSetAndGetOutDetailNo(): void
    {
        $transferReceipt = new TransferReceipt();
        $outDetailNo = 'test_detail_001';

        $transferReceipt->setOutDetailNo($outDetailNo);
        $this->assertEquals($outDetailNo, $transferReceipt->getOutDetailNo());
    }

    public function testSetAndGetBatchId(): void
    {
        $transferReceipt = new TransferReceipt();
        $batchId = 'wx_batch_123456';

        $transferReceipt->setBatchId($batchId);
        $this->assertEquals($batchId, $transferReceipt->getBatchId());
    }

    public function testSetAndGetDetailId(): void
    {
        $transferReceipt = new TransferReceipt();
        $detailId = 'wx_detail_123456';

        $transferReceipt->setDetailId($detailId);
        $this->assertEquals($detailId, $transferReceipt->getDetailId());
    }

    public function testSetAndGetReceiptType(): void
    {
        $transferReceipt = new TransferReceipt();
        $receiptType = 'TRANSACTION_DETAIL';

        $transferReceipt->setReceiptType($receiptType);
        $this->assertEquals($receiptType, $transferReceipt->getReceiptType());
    }

    public function testSetAndGetReceiptStatus(): void
    {
        $transferReceipt = new TransferReceipt();
        $status = TransferReceiptStatus::GENERATING;

        $transferReceipt->setReceiptStatus($status);
        $this->assertSame($status, $transferReceipt->getReceiptStatus());
        $this->assertEquals(TransferReceiptStatus::GENERATING, $transferReceipt->getReceiptStatus());
    }

    public function testDefaultReceiptStatus(): void
    {
        $transferReceipt = new TransferReceipt();
        // 测试默认状态是生成中
        $this->assertEquals(TransferReceiptStatus::GENERATING, $transferReceipt->getReceiptStatus());
    }

    public function testSetAndGetDownloadUrl(): void
    {
        $transferReceipt = new TransferReceipt();
        $downloadUrl = 'https://download.example.com/receipt.pdf';

        $transferReceipt->setDownloadUrl($downloadUrl);
        $this->assertEquals($downloadUrl, $transferReceipt->getDownloadUrl());
    }

    public function testSetAndGetHashValue(): void
    {
        $transferReceipt = new TransferReceipt();
        $hashValue = 'sha256_hash_123456';

        $transferReceipt->setHashValue($hashValue);
        $this->assertEquals($hashValue, $transferReceipt->getHashValue());
    }

    public function testSetAndGetGenerateTime(): void
    {
        $transferReceipt = new TransferReceipt();
        $generateTime = new \DateTimeImmutable('2024-12-01 12:00:00');

        $transferReceipt->setGenerateTime($generateTime);
        $this->assertSame($generateTime, $transferReceipt->getGenerateTime());
    }

    public function testSetAndGetExpireTime(): void
    {
        $transferReceipt = new TransferReceipt();
        $expireTime = new \DateTimeImmutable('2024-12-31 12:00:00');

        $transferReceipt->setExpireTime($expireTime);
        $this->assertSame($expireTime, $transferReceipt->getExpireTime());
    }

    public function testSetAndGetFileName(): void
    {
        $transferReceipt = new TransferReceipt();
        $fileName = 'transfer_receipt_001.pdf';

        $transferReceipt->setFileName($fileName);
        $this->assertEquals($fileName, $transferReceipt->getFileName());
    }

    public function testSetAndGetFileSize(): void
    {
        $transferReceipt = new TransferReceipt();
        $fileSize = 1024;

        $transferReceipt->setFileSize($fileSize);
        $this->assertEquals($fileSize, $transferReceipt->getFileSize());
    }

    public function testSetAndGetRawResponse(): void
    {
        $transferReceipt = new TransferReceipt();
        $rawResponse = '{"status": "success", "data": {}}';

        $transferReceipt->setRawResponse($rawResponse);
        $this->assertEquals($rawResponse, $transferReceipt->getRawResponse());
    }

    public function testSetAndGetApplyNo(): void
    {
        $transferReceipt = new TransferReceipt();
        $applyNo = 'apply_123456';

        $transferReceipt->setApplyNo($applyNo);
        $this->assertEquals($applyNo, $transferReceipt->getApplyNo());
    }

    public function testSetAndGetApplyTime(): void
    {
        $transferReceipt = new TransferReceipt();
        $applyTime = new \DateTimeImmutable('2024-12-01 12:00:00');

        $transferReceipt->setApplyTime($applyTime);
        $this->assertSame($applyTime, $transferReceipt->getApplyTime());
    }

    public function testToString(): void
    {
        $transferReceipt = new TransferReceipt();
        // 测试__toString方法返回ID
        $this->assertEquals('', (string)$transferReceipt);

        // 设置ID后测试
        $reflection = new \ReflectionClass($transferReceipt);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($transferReceipt, 123);

        $this->assertEquals('123', (string)$transferReceipt);
    }

    public function testReceiptStatusChangeFlow(): void
    {
        $transferReceipt = new TransferReceipt();

        // 测试状态流转：生成中 -> 可用 -> 已下载
        $transferReceipt->setReceiptStatus(TransferReceiptStatus::GENERATING);
        $this->assertEquals(TransferReceiptStatus::GENERATING, $transferReceipt->getReceiptStatus());

        $transferReceipt->setReceiptStatus(TransferReceiptStatus::AVAILABLE);
        $this->assertEquals(TransferReceiptStatus::AVAILABLE, $transferReceipt->getReceiptStatus());

        $transferReceipt->setReceiptStatus(TransferReceiptStatus::DOWNLOADED);
        $this->assertEquals(TransferReceiptStatus::DOWNLOADED, $transferReceipt->getReceiptStatus());
    }

    public function testCompleteReceiptData(): void
    {
        $transferReceipt = new TransferReceipt();
        $transferBatch = $this->createTestBatch();
        $transferDetail = $this->createTestDetail();

        // 测试设置完整的回单数据
        $transferReceipt->setTransferBatch($transferBatch);
        $transferReceipt->setTransferDetail($transferDetail);
        $transferReceipt->setOutBatchNo('test_batch_001');
        $transferReceipt->setOutDetailNo('test_detail_001');
        $transferReceipt->setBatchId('wx_batch_123');
        $transferReceipt->setDetailId('wx_detail_123');
        $transferReceipt->setReceiptType('TRANSACTION_DETAIL');
        $transferReceipt->setReceiptStatus(TransferReceiptStatus::AVAILABLE);
        $transferReceipt->setDownloadUrl('https://example.com/receipt.pdf');
        $transferReceipt->setHashValue('sha256_hash');
        $transferReceipt->setGenerateTime(new \DateTimeImmutable('2024-12-01 12:00:00'));
        $transferReceipt->setExpireTime(new \DateTimeImmutable('2024-12-31 12:00:00'));
        $transferReceipt->setFileName('receipt.pdf');
        $transferReceipt->setFileSize(2048);
        $transferReceipt->setApplyNo('apply_123');
        $transferReceipt->setApplyTime(new \DateTimeImmutable('2024-12-01 12:00:00'));
        $transferReceipt->setRawResponse('{"success": true}');

        // 验证所有数据都正确设置
        $this->assertSame($transferBatch, $transferReceipt->getTransferBatch());
        $this->assertSame($transferDetail, $transferReceipt->getTransferDetail());
        $this->assertEquals('test_batch_001', $transferReceipt->getOutBatchNo());
        $this->assertEquals('test_detail_001', $transferReceipt->getOutDetailNo());
        $this->assertEquals('wx_batch_123', $transferReceipt->getBatchId());
        $this->assertEquals('wx_detail_123', $transferReceipt->getDetailId());
        $this->assertEquals('TRANSACTION_DETAIL', $transferReceipt->getReceiptType());
        $this->assertEquals(TransferReceiptStatus::AVAILABLE, $transferReceipt->getReceiptStatus());
        $this->assertEquals('https://example.com/receipt.pdf', $transferReceipt->getDownloadUrl());
        $this->assertEquals('sha256_hash', $transferReceipt->getHashValue());
        $this->assertEquals(new \DateTimeImmutable('2024-12-01 12:00:00'), $transferReceipt->getGenerateTime());
        $this->assertEquals(new \DateTimeImmutable('2024-12-31 12:00:00'), $transferReceipt->getExpireTime());
        $this->assertEquals('receipt.pdf', $transferReceipt->getFileName());
        $this->assertEquals(2048, $transferReceipt->getFileSize());
        $this->assertEquals('apply_123', $transferReceipt->getApplyNo());
        $this->assertEquals('{"success": true}', $transferReceipt->getRawResponse());
    }
}