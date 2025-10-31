<?php

namespace WechatPayTransferBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use WechatPayBundle\Entity\Merchant;
use WechatPayBundle\Service\WechatPayClient;
use WechatPayTransferBundle\Entity\TransferBatch;
use WechatPayTransferBundle\Entity\TransferDetail;
use WechatPayTransferBundle\Enum\TransferBatchStatus;
use WechatPayTransferBundle\Enum\TransferDetailStatus;
use WechatPayTransferBundle\Service\TransferApiService;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
#[CoversClass(TransferApiService::class)]
#[RunTestsInSeparateProcesses]
final class TransferApiServiceTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 使用容器获取服务，不需要手动创建mock
    }

    protected function createService(): TransferApiService
    {
        return self::getService(TransferApiService::class);
    }

    public function testServiceExists(): void
    {
        $service = $this->createService();
        $this->assertInstanceOf(TransferApiService::class, $service);
    }

    public function testInitiateTransferSuccess(): void
    {
        $service = $this->createService();

        // 创建测试数据
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
        $transferBatch->setTotalAmount(1000);
        $transferBatch->setTotalNum(1);
        $transferBatch->setAppId('test_app_id');

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

        // Mock HTTP 响应
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $mockResponse->method('toArray')->willReturn([
            'batch_id' => 'wx_batch_123456',
            'out_batch_no' => $transferBatch->getOutBatchNo(),
        ]);

        // Mock HTTP Client
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($mockResponse);

        // 替换服务中的 HTTP Client
        $reflection = new \ReflectionClass($service);
        $httpClientProperty = $reflection->getProperty('httpClient');
        $httpClientProperty->setAccessible(true);
        $httpClientProperty->setValue($service, $httpClient);

        $result = $service->initiateTransfer($transferBatch);

        $this->assertIsArray($result);
        $this->assertSame('wx_batch_123456', $result['batch_id']);
        $this->assertSame(TransferBatchStatus::PROCESSING, $transferBatch->getBatchStatus());
        $this->assertSame('wx_batch_123456', $transferBatch->getBatchId());
        $this->assertSame(TransferDetailStatus::WAIT_PAY, $transferDetail->getDetailStatus());
    }

    public function testInitiateTransferFailsWithoutMerchant(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('转账批次必须关联商户');

        $service = $this->createService();
        $transferBatch = new TransferBatch();

        $service->initiateTransfer($transferBatch);
    }

    public function testInitiateTransferFailsWithoutAppId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('转账批次必须设置应用ID');

        $service = $this->createService();

        $merchant = new Merchant();
        $merchant->setMchId('test_merchant');
        $merchant->setApiKey('test_api_key');
        $merchant->setCertSerial('1234567890ABCDEF');

        $transferBatch = new TransferBatch();
        $transferBatch->setMerchant($merchant);

        $service->initiateTransfer($transferBatch);
    }

    public function testInitiateTransferHandlesApiError(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('发起转账失败: API Error');

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
        $transferBatch->setAppId('test_app_id');

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
            'message' => 'API Error',
        ]);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($mockResponse);

        $reflection = new \ReflectionClass($service);
        $httpClientProperty = $reflection->getProperty('httpClient');
        $httpClientProperty->setAccessible(true);
        $httpClientProperty->setValue($service, $httpClient);

        $service->initiateTransfer($transferBatch);
    }

    public function testQueryTransferByBatchIdSuccess(): void
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
        $transferBatch->setTotalAmount(1000);
        $transferBatch->setTotalNum(1);
        $transferBatch->setBatchId('wx_batch_123456');

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

        // Mock HTTP 响应
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $mockResponse->method('toArray')->willReturn([
            'batch_id' => 'wx_batch_123456',
            'out_batch_no' => $transferBatch->getOutBatchNo(),
            'batch_status' => 'SUCCESS',
            'transfer_detail_list' => [
                [
                    'out_detail_no' => $transferDetail->getOutDetailNo(),
                    'detail_id' => 'wx_detail_123456',
                    'detail_status' => 'SUCCESS',
                ],
            ],
        ]);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($mockResponse);

        $reflection = new \ReflectionClass($service);
        $httpClientProperty = $reflection->getProperty('httpClient');
        $httpClientProperty->setAccessible(true);
        $httpClientProperty->setValue($service, $httpClient);

        $result = $service->queryTransferByBatchId('wx_batch_123456');

        $this->assertIsArray($result);
        $this->assertSame('wx_batch_123456', $result['batch_id']);
        $this->assertSame('SUCCESS', $result['batch_status']);
    }

    public function testQueryTransferByBatchIdFails(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('查询转账失败: API Error');

        $service = $this->createService();

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

        $service->queryTransferByBatchId('invalid_batch_id');
    }

    public function testQueryTransferByOutBatchNoSuccess(): void
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

        // Mock HTTP 响应
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $mockResponse->method('toArray')->willReturn([
            'batch_id' => 'wx_batch_123456',
            'out_batch_no' => $transferBatch->getOutBatchNo(),
            'batch_status' => 'PROCESSING',
            'transfer_detail_list' => [],
        ]);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($mockResponse);

        $reflection = new \ReflectionClass($service);
        $httpClientProperty = $reflection->getProperty('httpClient');
        $httpClientProperty->setAccessible(true);
        $httpClientProperty->setValue($service, $httpClient);

        $result = $service->queryTransferByOutBatchNo($transferBatch->getOutBatchNo());

        $this->assertIsArray($result);
        $this->assertSame('wx_batch_123456', $result['batch_id']);
        $this->assertSame($transferBatch->getOutBatchNo(), $result['out_batch_no']);
        $this->assertSame('PROCESSING', $result['batch_status']);
    }

    public function testQueryTransferByOutBatchNoFails(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('查询转账失败: Not Found');

        $service = $this->createService();

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

        $service->queryTransferByOutBatchNo('non_existent_batch');
    }

    public function testSetupTransferNotificationSuccess(): void
    {
        $service = $this->createService();
        $notifyUrl = 'https://example.com/notify';
        $mchid = 'test_mchid';

        // Mock HTTP 响应
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $mockResponse->method('toArray')->willReturn([
            'mchid' => $mchid,
            'notify_url' => $notifyUrl,
        ]);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($mockResponse);

        $reflection = new \ReflectionClass($service);
        $httpClientProperty = $reflection->getProperty('httpClient');
        $httpClientProperty->setAccessible(true);
        $httpClientProperty->setValue($service, $httpClient);

        $result = $service->setupTransferNotification($notifyUrl, $mchid);

        $this->assertIsArray($result);
        $this->assertSame($mchid, $result['mchid']);
        $this->assertSame($notifyUrl, $result['notify_url']);
    }

    public function testSetupTransferNotificationFails(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('设置转账回调通知失败: Invalid URL');

        $service = $this->createService();
        $notifyUrl = 'invalid-url';

        // Mock HTTP 响应失败
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(Response::HTTP_BAD_REQUEST);
        $mockResponse->method('toArray')->willReturn([
            'message' => 'Invalid URL',
        ]);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($mockResponse);

        $reflection = new \ReflectionClass($service);
        $httpClientProperty = $reflection->getProperty('httpClient');
        $httpClientProperty->setAccessible(true);
        $httpClientProperty->setValue($service, $httpClient);

        $service->setupTransferNotification($notifyUrl);
    }

    public function testCancelTransferSuccess(): void
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

        // Mock HTTP 响应
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $mockResponse->method('toArray')->willReturn([
            'out_batch_no' => $transferBatch->getOutBatchNo(),
            'status' => 'CANCELLED',
        ]);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($mockResponse);

        $reflection = new \ReflectionClass($service);
        $httpClientProperty = $reflection->getProperty('httpClient');
        $httpClientProperty->setAccessible(true);
        $httpClientProperty->setValue($service, $httpClient);

        $result = $service->cancelTransfer($transferBatch->getOutBatchNo());

        $this->assertIsArray($result);
        $this->assertSame($transferBatch->getOutBatchNo(), $result['out_batch_no']);
        $this->assertSame(TransferBatchStatus::CLOSED, $transferBatch->getBatchStatus());
        $this->assertSame(TransferDetailStatus::FAIL, $transferDetail->getDetailStatus());
    }

    public function testCancelTransferFailsWithInvalidBatchNo(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('撤销转账失败: Batch not found');

        $service = $this->createService();

        // Mock HTTP 响应失败
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(Response::HTTP_NOT_FOUND);
        $mockResponse->method('toArray')->willReturn([
            'message' => 'Batch not found',
        ]);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($mockResponse);

        $reflection = new \ReflectionClass($service);
        $httpClientProperty = $reflection->getProperty('httpClient');
        $httpClientProperty->setAccessible(true);
        $httpClientProperty->setValue($service, $httpClient);

        $service->cancelTransfer('non_existent_batch');
    }

    public function testCancelTransferUpdatesLocalData(): void
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
        $transferBatch->setBatchStatus(TransferBatchStatus::PROCESSING);

        $transferDetail1 = new TransferDetail();
        $transferDetail1->setBatch($transferBatch);
        $transferDetail1->setOutDetailNo('TEST_DETAIL_1_' . uniqid());
        $transferDetail1->setTransferAmount(1000);
        $transferDetail1->setTransferRemark('测试明细1');
        $transferDetail1->setOpenid('test_openid_1');
        $transferDetail1->setDetailStatus(TransferDetailStatus::WAIT_PAY);

        $transferDetail2 = new TransferDetail();
        $transferDetail2->setBatch($transferBatch);
        $transferDetail2->setOutDetailNo('TEST_DETAIL_2_' . uniqid());
        $transferDetail2->setTransferAmount(1000);
        $transferDetail2->setTransferRemark('测试明细2');
        $transferDetail2->setOpenid('test_openid_2');
        $transferDetail2->setDetailStatus(TransferDetailStatus::WAIT_PAY);

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
            'out_batch_no' => $transferBatch->getOutBatchNo(),
            'status' => 'CANCELLED',
        ]);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($mockResponse);

        $reflection = new \ReflectionClass($service);
        $httpClientProperty = $reflection->getProperty('httpClient');
        $httpClientProperty->setAccessible(true);
        $httpClientProperty->setValue($service, $httpClient);

        $service->cancelTransfer($transferBatch->getOutBatchNo());

        // 验证所有明细状态都已更新为失败
        $this->assertSame(TransferBatchStatus::CLOSED, $transferBatch->getBatchStatus());
        $this->assertSame(TransferDetailStatus::FAIL, $transferDetail1->getDetailStatus());
        $this->assertSame(TransferDetailStatus::FAIL, $transferDetail2->getDetailStatus());
    }

    public function testGenerateAppConfirmParametersSuccess(): void
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
        $transferBatch->setAppId('test_app_id');
        $transferBatch->setOutBatchNo('TEST_BATCH_' . uniqid());

        $transferDetail = new TransferDetail();
        $transferDetail->setBatch($transferBatch);
        $transferDetail->setOutDetailNo('TEST_DETAIL_' . uniqid());
        $transferDetail->setDetailId('wx_detail_123456');
        $transferDetail->setTransferAmount(1000);
        $transferDetail->setTransferRemark('测试明细');
        $transferDetail->setOpenid('test_openid');

        $transferBatch->addDetail($transferDetail);
        self::getEntityManager()->persist($transferBatch);
        self::getEntityManager()->persist($transferDetail);
        self::getEntityManager()->flush();

        $result = $service->generateAppConfirmParameters($transferDetail);

        $this->assertIsArray($result);
        $this->assertSame('test_app_id', $result['appid']);
        $this->assertSame('transfer_detail_id=wx_detail_123456', $result['package']);
        $this->assertSame('RSA', $result['sign_type']);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertIsString($result['timestamp']);
    }

    public function testGenerateAppConfirmParametersFailsWithoutDetailId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('转账明细必须有微信明细单号');

        $service = $this->createService();

        // 创建测试数据（没有微信明细单号）
        $merchant = new Merchant();
        $merchant->setMchId('test_merchant');
        $merchant->setApiKey('test_api_key');
        $merchant->setCertSerial('1234567890ABCDEF');

        $transferBatch = new TransferBatch();
        $transferBatch->setMerchant($merchant);
        $transferBatch->setAppId('test_app_id');

        $transferDetail = new TransferDetail();
        $transferDetail->setBatch($transferBatch);
        // 没有设置 detailId

        $service->generateAppConfirmParameters($transferDetail);
    }

    public function testGenerateAppConfirmParametersFailsWithoutMerchant(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('转账批次必须关联商户');

        $service = $this->createService();

        // 创建测试数据（没有商户）
        $transferBatch = new TransferBatch();
        $transferBatch->setAppId('test_app_id');

        $transferDetail = new TransferDetail();
        $transferDetail->setBatch($transferBatch);
        $transferDetail->setDetailId('wx_detail_123456');

        $service->generateAppConfirmParameters($transferDetail);
    }

    public function testGenerateAppConfirmParametersFailsWithoutAppId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('转账明细必须关联到设置了应用ID的批次');

        $service = $this->createService();

        // 创建测试数据（没有应用ID）
        $merchant = new Merchant();
        $merchant->setMchId('test_merchant');
        $merchant->setApiKey('test_api_key');
        $merchant->setCertSerial('1234567890ABCDEF');

        $transferBatch = new TransferBatch();
        $transferBatch->setMerchant($merchant);
        // 没有设置 AppId

        $transferDetail = new TransferDetail();
        $transferDetail->setBatch($transferBatch);
        $transferDetail->setDetailId('wx_detail_123456');

        $service->generateAppConfirmParameters($transferDetail);
    }

    public function testGenerateJsApiConfirmParametersSuccess(): void
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
        $transferBatch->setAppId('test_app_id');
        $transferBatch->setOutBatchNo('TEST_BATCH_' . uniqid());

        $transferDetail = new TransferDetail();
        $transferDetail->setBatch($transferBatch);
        $transferDetail->setOutDetailNo('TEST_DETAIL_' . uniqid());
        $transferDetail->setDetailId('wx_detail_123456');
        $transferDetail->setTransferAmount(1000);
        $transferDetail->setTransferRemark('测试明细');
        $transferDetail->setOpenid('test_openid');

        $transferBatch->addDetail($transferDetail);
        self::getEntityManager()->persist($transferBatch);
        self::getEntityManager()->persist($transferDetail);
        self::getEntityManager()->flush();

        $openid = 'test_user_openid';
        $result = $service->generateJsApiConfirmParameters($transferDetail, $openid);

        $this->assertIsArray($result);
        $this->assertSame('test_app_id', $result['appId']);
        $this->assertSame('transfer_detail_id=wx_detail_123456', $result['package']);
        $this->assertSame('RSA', $result['signType']);
        $this->assertSame($openid, $result['openid']);
        $this->assertArrayHasKey('timeStamp', $result);
        $this->assertArrayHasKey('nonceStr', $result);
        $this->assertIsString($result['timeStamp']);
        $this->assertIsString($result['nonceStr']);
        $this->assertStringStartsWith('transfer_', $result['nonceStr']);
    }

    public function testGenerateJsApiConfirmParametersFailsWithoutDetailId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('转账明细必须有微信明细单号');

        $service = $this->createService();

        // 创建测试数据（没有微信明细单号）
        $merchant = new Merchant();
        $merchant->setMchId('test_merchant');
        $merchant->setApiKey('test_api_key');
        $merchant->setCertSerial('1234567890ABCDEF');

        $transferBatch = new TransferBatch();
        $transferBatch->setMerchant($merchant);
        $transferBatch->setAppId('test_app_id');

        $transferDetail = new TransferDetail();
        $transferDetail->setBatch($transferBatch);
        // 没有设置 detailId

        $service->generateJsApiConfirmParameters($transferDetail, 'test_openid');
    }

    public function testGenerateJsApiConfirmParametersFailsWithoutMerchant(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('转账批次必须关联商户');

        $service = $this->createService();

        // 创建测试数据（没有商户）
        $transferBatch = new TransferBatch();
        $transferBatch->setAppId('test_app_id');

        $transferDetail = new TransferDetail();
        $transferDetail->setBatch($transferBatch);
        $transferDetail->setDetailId('wx_detail_123456');

        $service->generateJsApiConfirmParameters($transferDetail, 'test_openid');
    }

    public function testGenerateJsApiConfirmParametersFailsWithoutAppId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('转账批次必须设置应用ID');

        $service = $this->createService();

        // 创建测试数据（没有应用ID）
        $merchant = new Merchant();
        $merchant->setMchId('test_merchant');
        $merchant->setApiKey('test_api_key');
        $merchant->setCertSerial('1234567890ABCDEF');

        $transferBatch = new TransferBatch();
        $transferBatch->setMerchant($merchant);
        // 没有设置 AppId

        $transferDetail = new TransferDetail();
        $transferDetail->setBatch($transferBatch);
        $transferDetail->setDetailId('wx_detail_123456');

        $service->generateJsApiConfirmParameters($transferDetail, 'test_openid');
    }

    public function testHandleTransferNotificationSuccess(): void
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
        $transferBatch->setBatchStatus(TransferBatchStatus::PROCESSING);

        $transferDetail = new TransferDetail();
        $transferDetail->setBatch($transferBatch);
        $transferDetail->setOutDetailNo('TEST_DETAIL_' . uniqid());
        $transferDetail->setTransferAmount(1000);
        $transferDetail->setTransferRemark('测试明细');
        $transferDetail->setOpenid('test_openid');
        $transferDetail->setDetailStatus(TransferDetailStatus::WAIT_PAY);

        $transferBatch->addDetail($transferDetail);
        self::getEntityManager()->persist($transferBatch);
        self::getEntityManager()->persist($transferDetail);
        self::getEntityManager()->flush();

        // 模拟回调通知数据
        $notificationData = [
            'event' => 'TRANSFER.SUCCESS',
            'resource' => [
                'ciphertext' => 'encrypted_data',
                'nonce' => 'nonce_value',
                'associated_data' => 'associated_data',
                // decryptNotificationData 方法目前直接返回 resource 数组
                'out_batch_no' => $transferBatch->getOutBatchNo(),
                'batch_status' => 'SUCCESS',
                'transfer_detail_list' => [
                    [
                        'out_detail_no' => $transferDetail->getOutDetailNo(),
                        'detail_status' => 'SUCCESS',
                    ],
                ],
            ],
        ];

        $result = $service->handleTransferNotification($notificationData);

        $this->assertTrue($result);
        // 由于 decryptNotificationData 方法目前直接返回 resource 数组
        // 这里主要测试不会抛出异常且返回 true
    }

    public function testHandleTransferNotificationFailsWithInvalidData(): void
    {
        $service = $this->createService();

        // 无效的回调数据（缺少 event 和 resource）
        $invalidNotificationData = [
            'invalid_field' => 'invalid_value',
        ];

        $result = $service->handleTransferNotification($invalidNotificationData);

        $this->assertFalse($result);
    }

    public function testHandleTransferNotificationFailsWithMissingBatchNo(): void
    {
        $service = $this->createService();

        // 缺少商户批次单号的回调数据
        $notificationData = [
            'event' => 'TRANSFER.SUCCESS',
            'resource' => [
                'ciphertext' => 'encrypted_data',
                'nonce' => 'nonce_value',
                'associated_data' => 'associated_data',
                // 缺少 out_batch_no
            ],
        ];

        $result = $service->handleTransferNotification($notificationData);

        $this->assertFalse($result);
    }

    public function testHandleTransferNotificationFailsWithNonExistentBatch(): void
    {
        $service = $this->createService();

        // 不存在的批次号
        $notificationData = [
            'event' => 'TRANSFER.SUCCESS',
            'resource' => [
                'ciphertext' => 'encrypted_data',
                'nonce' => 'nonce_value',
                'associated_data' => 'associated_data',
                'out_batch_no' => 'NON_EXISTENT_BATCH',
            ],
        ];

        $result = $service->handleTransferNotification($notificationData);

        $this->assertFalse($result);
    }

    public function testHandleTransferNotificationWithDifferentEvents(): void
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

        // 测试不同的事件类型
        $events = ['TRANSFER.SUCCESS', 'TRANSFER.FAIL', 'TRANSFER.CLOSE'];

        foreach ($events as $event) {
            $notificationData = [
                'event' => $event,
                'resource' => [
                    'ciphertext' => 'encrypted_data',
                    'nonce' => 'nonce_value',
                    'associated_data' => 'associated_data',
                    'out_batch_no' => $transferBatch->getOutBatchNo(),
                ],
            ];

            $result = $service->handleTransferNotification($notificationData);
            $this->assertTrue($result, "Event {$event} should be handled successfully");
        }
    }

    public function testHandleTransferNotificationWithEmptyResource(): void
    {
        $service = $this->createService();

        // 空的 resource 数组
        $notificationData = [
            'event' => 'TRANSFER.SUCCESS',
            'resource' => [],
        ];

        $result = $service->handleTransferNotification($notificationData);

        $this->assertFalse($result);
    }
}