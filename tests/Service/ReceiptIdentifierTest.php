<?php

declare(strict_types=1);

namespace WechatPayTransferBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use WechatPayTransferBundle\Service\ReceiptIdentifier;

#[CoversClass(ReceiptIdentifier::class)]
final class ReceiptIdentifierTest extends TestCase
{

    public function testCreateOutBatchNoIdentifier(): void
    {
        $identifier = ReceiptIdentifier::forOutBatchNo('BATCH_001');

        $this->assertSame('BATCH_001', $identifier->getValue());
        $this->assertSame('out_batch_no', $identifier->getType());
        $this->assertTrue($identifier->isOutBatchNo());
        $this->assertFalse($identifier->isBatchId());
        $this->assertFalse($identifier->isOutDetailNo());
        $this->assertFalse($identifier->isDetailId());
    }

    public function testCreateBatchIdIdentifier(): void
    {
        $identifier = ReceiptIdentifier::forBatchId(12345);

        $this->assertSame('12345', $identifier->getValue());
        $this->assertSame('batch_id', $identifier->getType());
        $this->assertFalse($identifier->isOutBatchNo());
        $this->assertTrue($identifier->isBatchId());
        $this->assertFalse($identifier->isOutDetailNo());
        $this->assertFalse($identifier->isDetailId());
    }

    public function testCreateOutDetailNoIdentifier(): void
    {
        $identifier = ReceiptIdentifier::forOutDetailNo('DETAIL_001');

        $this->assertSame('DETAIL_001', $identifier->getValue());
        $this->assertSame('out_detail_no', $identifier->getType());
        $this->assertFalse($identifier->isOutBatchNo());
        $this->assertFalse($identifier->isBatchId());
        $this->assertTrue($identifier->isOutDetailNo());
        $this->assertFalse($identifier->isDetailId());
    }

    public function testCreateDetailIdIdentifier(): void
    {
        $identifier = ReceiptIdentifier::forDetailId(67890);

        $this->assertSame('67890', $identifier->getValue());
        $this->assertSame('detail_id', $identifier->getType());
        $this->assertFalse($identifier->isOutBatchNo());
        $this->assertFalse($identifier->isBatchId());
        $this->assertFalse($identifier->isOutDetailNo());
        $this->assertTrue($identifier->isDetailId());
    }

    public function testBuildApiUrl(): void
    {
        $identifier = ReceiptIdentifier::forOutBatchNo('BATCH_001');
        $url = $identifier->buildApiUrl('apply');

        $this->assertSame('https://api.mch.weixin.qq.com/v3/transfer/bill-receipt/batch-nos/BATCH_001/receipts', $url);
    }

    public function testBuildApiUrlForQuery(): void
    {
        $identifier = ReceiptIdentifier::forBatchId(12345);
        $url = $identifier->buildApiUrl('query');

        $this->assertSame('https://api.mch.weixin.qq.com/v3/transfer/bill-receipt/batch-id/12345/receipts', $url);
    }

    public function testBuildRequestData(): void
    {
        $identifier = ReceiptIdentifier::forOutBatchNo('BATCH_001');
        $data = $identifier->buildRequestData();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('out_batch_no', $data);
        $this->assertSame('BATCH_001', $data['out_batch_no']);
    }

    public function testBuildRequestDataForBatchId(): void
    {
        $identifier = ReceiptIdentifier::forBatchId(12345);
        $data = $identifier->buildRequestData();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('batch_id', $data);
        $this->assertSame(12345, $data['batch_id']);
    }

    public function testBuildLogData(): void
    {
        $identifier = ReceiptIdentifier::forOutDetailNo('DETAIL_001');
        $logData = $identifier->buildLogData();

        $this->assertIsArray($logData);
        $this->assertArrayHasKey('out_detail_no', $logData);
        $this->assertSame('DETAIL_001', $logData['out_detail_no']);
    }

    public function testBuildLogDataForDetailId(): void
    {
        $identifier = ReceiptIdentifier::forDetailId(67890);
        $logData = $identifier->buildLogData();

        $this->assertIsArray($logData);
        $this->assertArrayHasKey('detail_id', $logData);
        $this->assertSame(67890, $logData['detail_id']);
    }

    public function testToString(): void
    {
        $identifier = ReceiptIdentifier::forOutBatchNo('BATCH_001');
        $this->assertSame('BATCH_001', (string) $identifier);
    }

    public function testBatchIdToString(): void
    {
        $identifier = ReceiptIdentifier::forBatchId(12345);
        $this->assertSame('12345', (string) $identifier);
    }

    public function testBuildApplyRequestData(): void
    {
        $identifier = ReceiptIdentifier::forOutBatchNo('BATCH_001');
        $data = $identifier->buildApplyRequestData();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('out_batch_no', $data);
        $this->assertSame('BATCH_001', $data['out_batch_no']);
    }

    public function testBuildApplyRequestDataForBatchId(): void
    {
        $identifier = ReceiptIdentifier::forBatchId(12345);
        $data = $identifier->buildApplyRequestData();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('batch_id', $data);
        $this->assertSame(12345, $data['batch_id']);
    }

    public function testBuildQueryUrl(): void
    {
        $identifier = ReceiptIdentifier::forOutBatchNo('BATCH_001');
        $url = $identifier->buildQueryUrl();

        $this->assertStringContainsString('BATCH_001', $url);
        $this->assertStringContainsString('transfer/bill-receipt', $url);
    }

    public function testBuildQueryUrlForBatchId(): void
    {
        $identifier = ReceiptIdentifier::forBatchId(12345);
        $url = $identifier->buildQueryUrl();

        $this->assertStringContainsString('12345', $url);
        $this->assertStringContainsString('transfer/bill-receipt', $url);
    }

    #[TestWith(['out_batch_no', 'out_batch_no', true, false, false, false])]
    #[TestWith(['batch_id', 'batch_id', false, true, false, false])]
    #[TestWith(['out_detail_no', 'out_detail_no', false, false, true, false])]
    #[TestWith(['detail_id', 'detail_id', false, false, false, true])]
    public function testIdentifierTypes(string $method, string $expectedType, bool $isOutBatchNo, bool $isBatchId, bool $isOutDetailNo, bool $isDetailId): void
    {
        $identifier = match ($method) {
            'out_batch_no' => ReceiptIdentifier::forOutBatchNo('TEST'),
            'batch_id' => ReceiptIdentifier::forBatchId(1),
            'out_detail_no' => ReceiptIdentifier::forOutDetailNo('TEST'),
            'detail_id' => ReceiptIdentifier::forDetailId(1),
            default => throw new \InvalidArgumentException("Unsupported method: {$method}"),
        };

        $this->assertSame($expectedType, $identifier->getType());
        $this->assertSame($isOutBatchNo, $identifier->isOutBatchNo());
        $this->assertSame($isBatchId, $identifier->isBatchId());
        $this->assertSame($isOutDetailNo, $identifier->isOutDetailNo());
        $this->assertSame($isDetailId, $identifier->isDetailId());
    }
}