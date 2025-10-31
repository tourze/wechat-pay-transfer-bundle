<?php

namespace WechatPayTransferBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Response;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use WechatPayBundle\Entity\Merchant;
use WechatPayTransferBundle\Entity\TransferBatch;
use WechatPayTransferBundle\Entity\TransferDetail;
use WechatPayTransferBundle\Entity\TransferReceipt;
use WechatPayTransferBundle\Enum\TransferReceiptStatus;
use WechatPayTransferBundle\Service\TransferReceiptApiService;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
#[CoversClass(TransferReceiptApiService::class)]
#[RunTestsInSeparateProcesses]
final class TransferReceiptApiServiceTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 使用容器获取服务，不需要手动创建mock
    }

    protected function createService(): TransferReceiptApiService
    {
        return self::getService(TransferReceiptApiService::class);
    }

    public function testServiceExists(): void
    {
        $service = $this->createService();
        $this->assertInstanceOf(TransferReceiptApiService::class, $service);
    }

    public function testApplyReceiptByBatchIdSuccess(): void
    {
        $service = $this->createService();
        $batchId = 'wx_batch_123456';
        $detailId = 'wx_detail_123456';

        // 创建必需的关联数据
        $merchant = new Merchant();
        $merchant->setMchId('test_merchant_' . uniqid());
        $merchant->setApiKey('test_api_key');
        $merchant->setCertSerial('1234567890ABCDEF');
        self::getEntityManager()->persist($merchant);

        $transferBatch = new TransferBatch();
        $transferBatch->setMerchant($merchant);
        $transferBatch->setOutBatchNo('TEST_BATCH_' . uniqid());
        $transferBatch->setBatchName('测试批次');
        $transferBatch->setBatchRemark('测试转账批次');
        $transferBatch->setTotalAmount(1000);
        $transferBatch->setTotalNum(1);
        $transferBatch->setBatchId($batchId);

        $transferDetail = new TransferDetail();
        $transferDetail->setBatch($transferBatch);
        $transferDetail->setOutDetailNo('TEST_DETAIL_' . uniqid());
        $transferDetail->setTransferAmount(1000);
        $transferDetail->setTransferRemark('测试明细');
        $transferDetail->setOpenid('test_openid');
        $transferDetail->setDetailId($detailId);

        $transferBatch->addDetail($transferDetail);
        self::getEntityManager()->persist($transferBatch);
        self::getEntityManager()->persist($transferDetail);
        self::getEntityManager()->flush();

        // Mock HTTP 响应
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $mockResponse->method('toArray')->willReturn([
            'apply_no' => 'apply_123456',
            'batch_id' => $batchId,
            'detail_id' => $detailId,
        ]);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($mockResponse);

        $reflection = new \ReflectionClass($service);
        $httpClientProperty = $reflection->getProperty('httpClient');
        $httpClientProperty->setAccessible(true);
        $httpClientProperty->setValue($service, $httpClient);

        $result = $service->applyReceiptByBatchId($batchId, $detailId);

        $this->assertInstanceOf(TransferReceipt::class, $result);
        $this->assertSame($batchId, $result->getBatchId());
        $this->assertSame($detailId, $result->getDetailId());
        $this->assertSame('apply_123456', $result->getApplyNo());
        $this->assertSame(TransferReceiptStatus::GENERATING, $result->getReceiptStatus());
    }

    public function testApplyReceiptByBatchIdFails(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('申请电子回单失败: API Error');

        $service = $this->createService();
        $batchId = 'invalid_batch_id';

        // Mock HTTP 响应失败
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(Response::HTTP_BAD_REQUEST);
        $mockResponse->method('toArray')->willReturn([
            'message' => 'API Error',
        ]);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($mockResponse);

        $reflection = new \ReflectionClass($service);
        $httpClientProperty = $reflection->getProperty('httpClient');
        $httpClientProperty->setAccessible(true);
        $httpClientProperty->setValue($service, $httpClient);

        $service->applyReceiptByBatchId($batchId);
    }

    public function testApplyReceiptByOutBatchNoSuccess(): void
    {
        $service = $this->createService();
        $outBatchNo = 'TEST_BATCH_' . uniqid();
        $outDetailNo = 'TEST_DETAIL_' . uniqid();

        // 创建必需的关联数据
        $merchant = new Merchant();
        $merchant->setMchId('test_merchant_' . uniqid());
        $merchant->setApiKey('test_api_key');
        $merchant->setCertSerial('1234567890ABCDEF');
        self::getEntityManager()->persist($merchant);

        $transferBatch = new TransferBatch();
        $transferBatch->setMerchant($merchant);
        $transferBatch->setOutBatchNo($outBatchNo);
        $transferBatch->setBatchName('测试批次');
        $transferBatch->setBatchRemark('测试转账批次');
        $transferBatch->setTotalAmount(1000);
        $transferBatch->setTotalNum(1);

        $transferDetail = new TransferDetail();
        $transferDetail->setBatch($transferBatch);
        $transferDetail->setOutDetailNo($outDetailNo);
        $transferDetail->setTransferAmount(1000);
        $transferDetail->setTransferRemark('测试明细');
        $transferDetail->setOpenid('test_openid');

        $transferBatch->addDetail($transferDetail);
        self::getEntityManager()->persist($transferBatch);
        self::getEntityManager()->persist($transferDetail);
        self::getEntityManager()->flush();

        // Mock HTTP 响应
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $mockResponse->method('toArray')->willReturn([
            'apply_no' => 'apply_789012',
            'out_batch_no' => $outBatchNo,
            'out_detail_no' => $outDetailNo,
        ]);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($mockResponse);

        $reflection = new \ReflectionClass($service);
        $httpClientProperty = $reflection->getProperty('httpClient');
        $httpClientProperty->setAccessible(true);
        $httpClientProperty->setValue($service, $httpClient);

        $result = $service->applyReceiptByOutBatchNo($outBatchNo, $outDetailNo);

        $this->assertInstanceOf(TransferReceipt::class, $result);
        $this->assertSame($outBatchNo, $result->getOutBatchNo());
        $this->assertSame($outDetailNo, $result->getOutDetailNo());
        $this->assertSame('apply_789012', $result->getApplyNo());
        $this->assertSame(TransferReceiptStatus::GENERATING, $result->getReceiptStatus());
    }

    public function testApplyReceiptByOutBatchNoFails(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('申请电子回单失败: Not Found');

        $service = $this->createService();
        $outBatchNo = 'non_existent_batch';

        // Mock HTTP 响应失败
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(Response::HTTP_NOT_FOUND);
        $mockResponse->method('toArray')->willReturn([
            'message' => 'Not Found',
        ]);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($mockResponse);

        $reflection = new \ReflectionClass($service);
        $httpClientProperty = $reflection->getProperty('httpClient');
        $httpClientProperty->setAccessible(true);
        $httpClientProperty->setValue($service, $httpClient);

        $service->applyReceiptByOutBatchNo($outBatchNo);
    }

    public function testBatchApplyReceiptsSuccess(): void
    {
        $service = $this->createService();

        // 创建测试数据
        $merchant = new Merchant();
        $merchant->setMchId('test_merchant_' . uniqid());
        $merchant->setApiKey('test_api_key');
        $merchant->setCertSerial('1234567890ABCDEF');
        self::getEntityManager()->persist($merchant);

        $transferBatch = new TransferBatch();
        $transferBatch->setMerchant($merchant);
        $transferBatch->setOutBatchNo('TEST_BATCH_' . uniqid());
        $transferBatch->setBatchName('测试批次');
        $transferBatch->setBatchRemark('测试转账批次');
        $transferBatch->setTotalAmount(2000);
        $transferBatch->setTotalNum(2);

        $transferDetail1 = new TransferDetail();
        $transferDetail1->setBatch($transferBatch);
        $transferDetail1->setOutDetailNo('TEST_DETAIL_1_' . uniqid());
        $transferDetail1->setTransferAmount(1000);
        $transferDetail1->setTransferRemark('测试明细1');
        $transferDetail1->setOpenid('test_openid1');

        $transferDetail2 = new TransferDetail();
        $transferDetail2->setBatch($transferBatch);
        $transferDetail2->setOutDetailNo('TEST_DETAIL_2_' . uniqid());
        $transferDetail2->setTransferAmount(1000);
        $transferDetail2->setTransferRemark('测试明细2');
        $transferDetail2->setOpenid('test_openid2');

        $transferBatch->addDetail($transferDetail1);
        $transferBatch->addDetail($transferDetail2);
        self::getEntityManager()->persist($transferBatch);
        self::getEntityManager()->persist($transferDetail1);
        self::getEntityManager()->persist($transferDetail2);
        self::getEntityManager()->flush();

        // Mock HTTP 响应
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $mockResponse->method('toArray')->willReturn([
            'apply_no' => 'apply_batch_' . uniqid(),
            'out_batch_no' => $transferBatch->getOutBatchNo(), // 添加批次号以便查找关联
        ]);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($mockResponse);

        $reflection = new \ReflectionClass($service);
        $httpClientProperty = $reflection->getProperty('httpClient');
        $httpClientProperty->setAccessible(true);
        $httpClientProperty->setValue($service, $httpClient);

        $result = $service->batchApplyReceipts($transferBatch);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('batch', $result);
        $this->assertArrayHasKey('details', $result);
        $this->assertInstanceOf(TransferReceipt::class, $result['batch']);
        $this->assertCount(2, $result['details']);
        $this->assertInstanceOf(TransferReceipt::class, $result['details'][0]);
        $this->assertInstanceOf(TransferReceipt::class, $result['details'][1]);
    }

    public function testBatchApplyReceiptsFails(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('申请电子回单失败: Batch Error');

        $service = $this->createService();

        // 创建测试数据
        $merchant = new Merchant();
        $merchant->setMchId('test_merchant_' . uniqid());
        $merchant->setApiKey('test_api_key');
        $merchant->setCertSerial('1234567890ABCDEF');
        self::getEntityManager()->persist($merchant);

        $transferBatch = new TransferBatch();
        $transferBatch->setMerchant($merchant);
        $transferBatch->setOutBatchNo('TEST_BATCH_' . uniqid());
        $transferBatch->setBatchName('测试批次');
        $transferBatch->setBatchRemark('测试转账批次');
        $transferBatch->setTotalAmount(1000);
        $transferBatch->setTotalNum(1);

        $transferDetail = new TransferDetail();
        $transferDetail->setBatch($transferBatch);
        $transferDetail->setOutDetailNo('TEST_DETAIL_' . uniqid());
        $transferDetail->setTransferAmount(1000);
        $transferDetail->setTransferRemark('测试明细');
        $transferDetail->setOpenid('test_openid');

        $transferBatch->addDetail($transferDetail);
        self::getEntityManager()->persist($transferBatch);
        self::getEntityManager()->persist($transferDetail);
        self::getEntityManager()->flush();

        // Mock HTTP 响应失败
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(Response::HTTP_BAD_REQUEST);
        $mockResponse->method('toArray')->willReturn([
            'message' => 'Batch Error',
        ]);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($mockResponse);

        $reflection = new \ReflectionClass($service);
        $httpClientProperty = $reflection->getProperty('httpClient');
        $httpClientProperty->setAccessible(true);
        $httpClientProperty->setValue($service, $httpClient);

        $service->batchApplyReceipts($transferBatch);
    }

    public function testDownloadReceiptSuccess(): void
    {
        $service = $this->createService();
        $downloadUrl = 'https://example.com/receipt.pdf';
        $fileContent = 'PDF file content here';

        // Mock HTTP 响应
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $mockResponse->method('getContent')->willReturn($fileContent);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($mockResponse);

        $reflection = new \ReflectionClass($service);
        $httpClientProperty = $reflection->getProperty('httpClient');
        $httpClientProperty->setAccessible(true);
        $httpClientProperty->setValue($service, $httpClient);

        $result = $service->downloadReceipt($downloadUrl);

        $this->assertSame($fileContent, $result);
    }

    public function testDownloadReceiptFails(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('下载电子回单失败: HTTP 404');

        $service = $this->createService();
        $downloadUrl = 'https://example.com/not_found.pdf';

        // Mock HTTP 响应失败
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(Response::HTTP_NOT_FOUND);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($mockResponse);

        $reflection = new \ReflectionClass($service);
        $httpClientProperty = $reflection->getProperty('httpClient');
        $httpClientProperty->setAccessible(true);
        $httpClientProperty->setValue($service, $httpClient);

        $service->downloadReceipt($downloadUrl);
    }

    public function testQueryReceiptByBatchIdSuccess(): void
    {
        $service = $this->createService();
        $batchId = 'wx_batch_123456';
        $detailId = 'wx_detail_123456';

        // 创建测试数据
        $merchant = new Merchant();
        $merchant->setMchId('test_merchant_' . uniqid());
        $merchant->setApiKey('test_api_key');
        $merchant->setCertSerial('1234567890ABCDEF');
        self::getEntityManager()->persist($merchant);

        $transferBatch = new TransferBatch();
        $transferBatch->setMerchant($merchant);
        $transferBatch->setOutBatchNo('TEST_BATCH_' . uniqid());
        $transferBatch->setBatchName('测试批次');
        $transferBatch->setBatchRemark('测试转账批次');
        $transferBatch->setTotalAmount(1000);
        $transferBatch->setTotalNum(1);
        $transferBatch->setBatchId($batchId);

        $transferDetail = new TransferDetail();
        $transferDetail->setBatch($transferBatch);
        $transferDetail->setOutDetailNo('TEST_DETAIL_' . uniqid());
        $transferDetail->setTransferAmount(1000);
        $transferDetail->setTransferRemark('测试明细');
        $transferDetail->setOpenid('test_openid');
        $transferDetail->setDetailId($detailId);

        $transferBatch->addDetail($transferDetail);
        self::getEntityManager()->persist($transferBatch);
        self::getEntityManager()->persist($transferDetail);
        self::getEntityManager()->flush();

        // Mock HTTP 响应
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $mockResponse->method('toArray')->willReturn([
            'batch_id' => $batchId,
            'detail_id' => $detailId,
            'receipt_status' => 'AVAILABLE',
            'download_url' => 'https://example.com/receipt.pdf',
            'hash_value' => 'abc123def456',
            'generate_time' => '2024-01-01T10:00:00Z',
            'expire_time' => '2024-01-08T10:00:00Z',
            'file_name' => 'receipt_20240101.pdf',
            'file_size' => 1024,
        ]);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($mockResponse);

        $reflection = new \ReflectionClass($service);
        $httpClientProperty = $reflection->getProperty('httpClient');
        $httpClientProperty->setAccessible(true);
        $httpClientProperty->setValue($service, $httpClient);

        $result = $service->queryReceiptByBatchId($batchId, $detailId);

        $this->assertInstanceOf(TransferReceipt::class, $result);
        $this->assertSame($batchId, $result->getBatchId());
        $this->assertSame($detailId, $result->getDetailId());
        $this->assertSame(TransferReceiptStatus::AVAILABLE, $result->getReceiptStatus());
        $this->assertSame('https://example.com/receipt.pdf', $result->getDownloadUrl());
        $this->assertSame('abc123def456', $result->getHashValue());
        $this->assertSame('receipt_20240101.pdf', $result->getFileName());
        $this->assertSame(1024, $result->getFileSize());
    }

    public function testQueryReceiptByBatchIdFails(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('查询电子回单失败: Not Found');

        $service = $this->createService();
        $batchId = 'non_existent_batch';

        // Mock HTTP 响应失败
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(Response::HTTP_NOT_FOUND);
        $mockResponse->method('toArray')->willReturn([
            'message' => 'Not Found',
        ]);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($mockResponse);

        $reflection = new \ReflectionClass($service);
        $httpClientProperty = $reflection->getProperty('httpClient');
        $httpClientProperty->setAccessible(true);
        $httpClientProperty->setValue($service, $httpClient);

        $service->queryReceiptByBatchId($batchId);
    }

    public function testQueryReceiptByOutBatchNoSuccess(): void
    {
        $service = $this->createService();
        $outBatchNo = 'TEST_BATCH_' . uniqid();
        $outDetailNo = 'TEST_DETAIL_' . uniqid();

        // 创建必需的关联数据
        $merchant = new Merchant();
        $merchant->setMchId('test_merchant_' . uniqid());
        $merchant->setApiKey('test_api_key');
        $merchant->setCertSerial('1234567890ABCDEF');
        self::getEntityManager()->persist($merchant);

        $transferBatch = new TransferBatch();
        $transferBatch->setMerchant($merchant);
        $transferBatch->setOutBatchNo($outBatchNo);
        $transferBatch->setBatchName('测试批次');
        $transferBatch->setBatchRemark('测试转账批次');
        $transferBatch->setTotalAmount(1000);
        $transferBatch->setTotalNum(1);

        $transferDetail = new TransferDetail();
        $transferDetail->setBatch($transferBatch);
        $transferDetail->setOutDetailNo($outDetailNo);
        $transferDetail->setTransferAmount(1000);
        $transferDetail->setTransferRemark('测试明细');
        $transferDetail->setOpenid('test_openid');

        $transferBatch->addDetail($transferDetail);
        self::getEntityManager()->persist($transferBatch);
        self::getEntityManager()->persist($transferDetail);
        self::getEntityManager()->flush();

        // Mock HTTP 响应
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $mockResponse->method('toArray')->willReturn([
            'out_batch_no' => $outBatchNo,
            'out_detail_no' => $outDetailNo,
            'receipt_status' => 'GENERATING',
        ]);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($mockResponse);

        $reflection = new \ReflectionClass($service);
        $httpClientProperty = $reflection->getProperty('httpClient');
        $httpClientProperty->setAccessible(true);
        $httpClientProperty->setValue($service, $httpClient);

        $result = $service->queryReceiptByOutBatchNo($outBatchNo, $outDetailNo);

        $this->assertInstanceOf(TransferReceipt::class, $result);
        $this->assertSame($outBatchNo, $result->getOutBatchNo());
        $this->assertSame($outDetailNo, $result->getOutDetailNo());
        $this->assertSame(TransferReceiptStatus::GENERATING, $result->getReceiptStatus());
    }

    public function testQueryReceiptByOutBatchNoFails(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('查询电子回单失败: API Error');

        $service = $this->createService();
        $outBatchNo = 'non_existent_batch';

        // Mock HTTP 响应失败
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(Response::HTTP_BAD_REQUEST);
        $mockResponse->method('toArray')->willReturn([
            'message' => 'API Error',
        ]);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($mockResponse);

        $reflection = new \ReflectionClass($service);
        $httpClientProperty = $reflection->getProperty('httpClient');
        $httpClientProperty->setAccessible(true);
        $httpClientProperty->setValue($service, $httpClient);

        $service->queryReceiptByOutBatchNo($outBatchNo);
    }
}