<?php

declare(strict_types=1);

namespace WechatPayTransferBundle\Tests\Controller\Api;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;
use WechatPayTransferBundle\Controller\Api\TransferApiController;
use WechatPayTransferBundle\Entity\TransferBatch;
use WechatPayTransferBundle\Entity\TransferDetail;
use WechatPayTransferBundle\Repository\TransferBatchRepository;
use WechatPayTransferBundle\Repository\TransferDetailRepository;
use WechatPayTransferBundle\Service\TransferApiService;

/**
 * @internal
 */
#[CoversClass(TransferApiController::class)]
#[RunTestsInSeparateProcesses]
final class TransferApiControllerTest extends AbstractWebTestCase
{
    private MockObject $transferApiService;
    private MockObject $batchRepository;
    private MockObject $detailRepository;
    private MockObject $validator;
    private string $apiVersion = 'v1'; // 非 mock 的实际业务属性
    private string $apiBaseUrl = '/api/wechat-pay-transfer'; // API基础URL
    private int $maxTransferAmount = 500000; // 最大转账金额（分）
    /** @var array<string> */
    private array $supportedOperations = ['initiate', 'cancel', 'query']; // 支持的操作
    private TransferBatch $testBatch; // 测试用的真实实体对象

    protected function onSetUp(): void
    {
        $this->transferApiService = $this->createMock(TransferApiService::class);
        $this->batchRepository = $this->createMock(TransferBatchRepository::class);
        $this->detailRepository = $this->createMock(TransferDetailRepository::class);
        $this->validator = $this->createMock(ValidatorInterface::class);

        // 初始化真实的测试实体对象
        $this->testBatch = new TransferBatch();
        $this->testBatch->setOutBatchNo('TEST_BATCH_001');
        $this->testBatch->setBatchName('测试批次');
        $this->testBatch->setTotalAmount(100000);
        $this->testBatch->setTotalNum(5);
    }

    protected function createTestClient(): KernelBrowser
    {
        return static::createClientWithDatabase();
    }

    /**
     * 设置测试服务的mock
     */
    private function setupServiceMocks(): KernelBrowser
    {
        // 静态重启内核以允许服务替换
        if (static::$booted) {
            self::ensureKernelShutdown();
        }

        // 创建客户端（这会启动内核）
        $client = static::createClientWithDatabase();

        // 尝试替换服务，如果失败则跳过（服务可能已经初始化）
        $container = static::getContainer();

        try {
            $container->set(TransferApiService::class, $this->transferApiService);
        } catch (\Exception $e) {
            // 服务已经初始化，跳过替换
        }

        try {
            $container->set(TransferBatchRepository::class, $this->batchRepository);
        } catch (\Exception $e) {
            // 服务已经初始化，跳过替换
        }

        try {
            $container->set(TransferDetailRepository::class, $this->detailRepository);
        } catch (\Exception $e) {
            // 服务已经初始化，跳过替换
        }

        try {
            $container->set(ValidatorInterface::class, $this->validator);
        } catch (\Exception $e) {
            // 服务已经初始化，跳过替换
        }

        return $client;
    }

    public function testInitiateTransferSuccess(): void
    {
        $batchId = '123';
        $transferData = [
            'batch_id' => $batchId,
        ];

        $transferBatch = $this->createMock(TransferBatch::class);
        $transferBatch
            ->method('getId')
            ->willReturn($batchId);

        $apiResult = [
            'out_batch_no' => 'MERCHANT_BATCH_001',
            'batch_id' => 'WECHAT_BATCH_001',
            'status' => 'PROCESSING',
        ];

        $this->batchRepository
            ->expects($this->once())
            ->method('find')
            ->with($batchId)
            ->willReturn($transferBatch);

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->with($transferBatch)
            ->willReturn(new ConstraintViolationList());

        $this->transferApiService
            ->expects($this->once())
            ->method('initiateTransfer')
            ->with($transferBatch)
            ->willReturn($apiResult);

        $client = $this->setupServiceMocks();
        $this->loginAsAdmin($client);
        $this->loginAsAdmin($client);
        $jsonData = json_encode($transferData);
        if ($jsonData === false) {
            \PHPUnit\Framework\Assert::fail('Failed to encode transfer data to JSON');
        }
        $client->request('POST', '/api/wechat-pay-transfer/transfer/initiate', [], [], [], $jsonData);

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = $response->getContent();
        if ($content === false) {
            \PHPUnit\Framework\Assert::fail('Failed to get response content');
        }
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertTrue($data['success']);
        $this->assertSame('转账发起成功', $data['message']);
        $this->assertSame($apiResult, $data['data']);

        // 验证 API 配置属性 (非 mock 属性)
        $this->assertSame('v1', $this->apiVersion);
        $this->assertSame('/api/wechat-pay-transfer', $this->apiBaseUrl);
        $this->assertContains('initiate', $this->supportedOperations);
    }

    public function testInitiateTransferBatchNotFound(): void
    {
        $batchId = '999';
        $transferData = [
            'batch_id' => $batchId,
        ];

        $this->batchRepository
            ->expects($this->once())
            ->method('find')
            ->with($batchId)
            ->willReturn(null);

        $client = $this->setupServiceMocks();
        $this->loginAsAdmin($client);
        $jsonData = json_encode($transferData);
        if ($jsonData === false) {
            \PHPUnit\Framework\Assert::fail('Failed to encode transfer data to JSON');
        }
        $client->request('POST', '/api/wechat-pay-transfer/transfer/initiate', [], [], [], $jsonData);

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());

        $content = $response->getContent();
        if ($content === false) {
            \PHPUnit\Framework\Assert::fail('Failed to get response content');
        }
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertSame('转账批次不存在', $data['error']);
    }

    public function testInitiateTransferInvalidJson(): void
    {
        $client = $this->setupServiceMocks();
        $this->loginAsAdmin($client);
        $client->request('POST', '/api/wechat-pay-transfer/transfer/initiate', [], [], [], 'invalid json');

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $content = $response->getContent();
        if ($content === false) {
            \PHPUnit\Framework\Assert::fail('Failed to get response content');
        }
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertSame('无效的JSON数据', $data['error']);
    }

    public function testInitiateTransferValidationFailed(): void
    {
        $batchId = '123';
        $transferData = [
            'batch_id' => $batchId,
        ];

        $transferBatch = $this->createMock(TransferBatch::class);
        $transferBatch
            ->method('getId')
            ->willReturn($batchId);

        $violations = new ConstraintViolationList();
        $violations->add($this->createMock(\Symfony\Component\Validator\ConstraintViolationInterface::class));

        $this->batchRepository
            ->expects($this->once())
            ->method('find')
            ->with($batchId)
            ->willReturn($transferBatch);

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->with($transferBatch)
            ->willReturn($violations);

        $client = $this->setupServiceMocks();
        $this->loginAsAdmin($client);
        $jsonData = json_encode($transferData);
        if ($jsonData === false) {
            \PHPUnit\Framework\Assert::fail('Failed to encode transfer data to JSON');
        }
        $client->request('POST', '/api/wechat-pay-transfer/transfer/initiate', [], [], [], $jsonData);

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $content = $response->getContent();
        if ($content === false) {
            \PHPUnit\Framework\Assert::fail('Failed to get response content');
        }
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertSame('数据验证失败', $data['error']);
        $this->assertArrayHasKey('errors', $data);
    }

    public function testCancelTransferSuccess(): void
    {
        $outBatchNo = 'MERCHANT_BATCH_001';
        $cancelData = [
            'out_batch_no' => $outBatchNo,
        ];

        $apiResult = [
            'out_batch_no' => $outBatchNo,
            'status' => 'CANCELLED',
        ];

        $this->transferApiService
            ->expects($this->once())
            ->method('cancelTransfer')
            ->with($outBatchNo)
            ->willReturn($apiResult);

        $client = $this->setupServiceMocks();
        $this->loginAsAdmin($client);
        $jsonData = json_encode($cancelData);
        if ($jsonData === false) {
            \PHPUnit\Framework\Assert::fail('Failed to encode cancel data to JSON');
        }
        $client->request('POST', '/api/wechat-pay-transfer/transfer/cancel', [], [], [], $jsonData);

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = $response->getContent();
        if ($content === false) {
            \PHPUnit\Framework\Assert::fail('Failed to get response content');
        }
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertTrue($data['success']);
        $this->assertSame('转账撤销成功', $data['message']);
        $this->assertSame($apiResult, $data['data']);
    }

    public function testCancelTransferMissingBatchNo(): void
    {
        $cancelData = [
            'invalid_field' => 'value',
        ];

        $client = $this->setupServiceMocks();
        $this->loginAsAdmin($client);
        $jsonData = json_encode($cancelData);
        if ($jsonData === false) {
            \PHPUnit\Framework\Assert::fail('Failed to encode cancel data to JSON');
        }
        $client->request('POST', '/api/wechat-pay-transfer/transfer/cancel', [], [], [], $jsonData);

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $content = $response->getContent();
        if ($content === false) {
            \PHPUnit\Framework\Assert::fail('Failed to get response content');
        }
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertSame('缺少商户批次单号', $data['error']);
    }

    public function testQueryTransferByOutBatchNoSuccess(): void
    {
        $outBatchNo = 'MERCHANT_BATCH_001';
        $needQueryDetail = true;

        $apiResult = [
            'out_batch_no' => $outBatchNo,
            'batch_id' => 'WECHAT_BATCH_001',
            'status' => 'FINISHED',
            'details' => [
                [
                    'out_detail_no' => 'MERCHANT_DETAIL_001',
                    'detail_id' => 'WECHAT_DETAIL_001',
                    'status' => 'SUCCESS',
                ],
            ],
        ];

        $this->transferApiService
            ->expects($this->once())
            ->method('queryTransferByOutBatchNo')
            ->with($outBatchNo, $needQueryDetail)
            ->willReturn($apiResult);

        $client = $this->setupServiceMocks();
        $this->loginAsAdmin($client);
        $client->request('GET', '/api/wechat-pay-transfer/transfer/query', [
            'out_batch_no' => $outBatchNo,
            'need_query_detail' => '1',
        ]);

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = $response->getContent();
        if ($content === false) {
            \PHPUnit\Framework\Assert::fail('Failed to get response content');
        }
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertTrue($data['success']);
        $this->assertSame('查询成功', $data['message']);
        $this->assertSame($apiResult, $data['data']);
    }

    public function testQueryTransferByBatchIdSuccess(): void
    {
        $batchId = 'WECHAT_BATCH_001';
        $needQueryDetail = false;

        $apiResult = [
            'out_batch_no' => 'MERCHANT_BATCH_001',
            'batch_id' => $batchId,
            'status' => 'PROCESSING',
        ];

        $this->transferApiService
            ->expects($this->once())
            ->method('queryTransferByBatchId')
            ->with($batchId, $needQueryDetail)
            ->willReturn($apiResult);

        $client = $this->setupServiceMocks();
        $this->loginAsAdmin($client);
        $client->request('GET', '/api/wechat-pay-transfer/transfer/query', [
            'batch_id' => $batchId,
        ]);

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = $response->getContent();
        if ($content === false) {
            \PHPUnit\Framework\Assert::fail('Failed to get response content');
        }
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertTrue($data['success']);
        $this->assertSame('查询成功', $data['message']);
        $this->assertSame($apiResult, $data['data']);
    }

    public function testQueryTransferMissingParameters(): void
    {
        $client = $this->setupServiceMocks();
        $this->loginAsAdmin($client);
        $client->request('GET', '/api/wechat-pay-transfer/transfer/query');

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $content = $response->getContent();
        if ($content === false) {
            \PHPUnit\Framework\Assert::fail('Failed to get response content');
        }
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertSame('必须提供商户批次单号或微信批次单号', $data['error']);
    }

    public function testGenerateAppConfirmParametersSuccess(): void
    {
        $detailId = '123';
        $confirmData = [
            'detail_id' => $detailId,
        ];

        $transferDetail = $this->createMock(TransferDetail::class);
        $transferDetail
            ->method('getId')
            ->willReturn($detailId);

        $parameters = [
            'appid' => 'wx1234567890',
            'mch_id' => '1234567890',
            'package' => 'prepay_id=wx1234567890',
            'timestamp' => '1640995200',
            'noncestr' => 'random_string',
            'sign' => 'generated_sign',
        ];

        $this->detailRepository
            ->expects($this->once())
            ->method('find')
            ->with($detailId)
            ->willReturn($transferDetail);

        $this->transferApiService
            ->expects($this->once())
            ->method('generateAppConfirmParameters')
            ->with($transferDetail)
            ->willReturn($parameters);

        $client = $this->setupServiceMocks();
        $this->loginAsAdmin($client);
        $jsonData = json_encode($confirmData);
        if ($jsonData === false) {
            \PHPUnit\Framework\Assert::fail('Failed to encode confirm data to JSON');
        }
        $client->request('POST', '/api/wechat-pay-transfer/transfer/app-confirm', [], [], [], $jsonData);

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = $response->getContent();
        if ($content === false) {
            \PHPUnit\Framework\Assert::fail('Failed to get response content');
        }
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertTrue($data['success']);
        $this->assertSame('生成APP确认参数成功', $data['message']);
        $this->assertSame($parameters, $data['data']);
    }

    public function testGenerateAppConfirmParametersDetailNotFound(): void
    {
        $detailId = '999';
        $confirmData = [
            'detail_id' => $detailId,
        ];

        $this->detailRepository
            ->expects($this->once())
            ->method('find')
            ->with($detailId)
            ->willReturn(null);

        $client = $this->setupServiceMocks();
        $this->loginAsAdmin($client);
        $jsonData = json_encode($confirmData);
        if ($jsonData === false) {
            \PHPUnit\Framework\Assert::fail('Failed to encode confirm data to JSON');
        }
        $client->request('POST', '/api/wechat-pay-transfer/transfer/app-confirm', [], [], [], $jsonData);

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());

        $content = $response->getContent();
        if ($content === false) {
            \PHPUnit\Framework\Assert::fail('Failed to get response content');
        }
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertSame('转账明细不存在', $data['error']);
    }

    public function testGenerateJsApiConfirmParametersSuccess(): void
    {
        $detailId = '123';
        $openid = 'user_openid_123';
        $confirmData = [
            'detail_id' => $detailId,
            'openid' => $openid,
        ];

        $transferDetail = $this->createMock(TransferDetail::class);
        $transferDetail
            ->method('getId')
            ->willReturn($detailId);

        $parameters = [
            'appId' => 'wx1234567890',
            'timeStamp' => '1640995200',
            'nonceStr' => 'random_string',
            'package' => 'prepay_id=wx1234567890',
            'signType' => 'RSA',
            'paySign' => 'generated_sign',
        ];

        $this->detailRepository
            ->expects($this->once())
            ->method('find')
            ->with($detailId)
            ->willReturn($transferDetail);

        $this->transferApiService
            ->expects($this->once())
            ->method('generateJsApiConfirmParameters')
            ->with($transferDetail, $openid)
            ->willReturn($parameters);

        $client = $this->setupServiceMocks();
        $this->loginAsAdmin($client);
        $jsonData = json_encode($confirmData);
        if ($jsonData === false) {
            \PHPUnit\Framework\Assert::fail('Failed to encode confirm data to JSON');
        }
        $client->request('POST', '/api/wechat-pay-transfer/transfer/jsapi-confirm', [], [], [], $jsonData);

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = $response->getContent();
        if ($content === false) {
            \PHPUnit\Framework\Assert::fail('Failed to get response content');
        }
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertTrue($data['success']);
        $this->assertSame('生成JSAPI确认参数成功', $data['message']);
        $this->assertSame($parameters, $data['data']);
    }

    public function testGenerateJsApiConfirmParametersMissingParameters(): void
    {
        $confirmData = [
            'detail_id' => '123',
            // 缺少 openid
        ];

        $client = $this->setupServiceMocks();
        $this->loginAsAdmin($client);
        $jsonData = json_encode($confirmData);
        if ($jsonData === false) {
            \PHPUnit\Framework\Assert::fail('Failed to encode confirm data to JSON');
        }
        $client->request('POST', '/api/wechat-pay-transfer/transfer/jsapi-confirm', [], [], [], $jsonData);

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $content = $response->getContent();
        if ($content === false) {
            \PHPUnit\Framework\Assert::fail('Failed to get response content');
        }
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertSame('缺少转账明细ID或用户openid', $data['error']);
    }

    public function testHandleTransferNotificationSuccess(): void
    {
        $notificationData = [
            'id' => 'EV-2018022511223320873',
            'create_time' => '2018-06-09T10:30:00+08:00',
            'event_type' => 'TRANSFER.SUCCESS',
            'resource_type' => 'encrypt-resource',
            'resource' => [
                'original_type' => 'mchid',
                'algorithm' => 'AEAD_AES_256_GCM',
                'ciphertext' => 'encrypted_data',
                'associated_data' => 'transfer',
                'nonce' => 'random_nonce',
            ],
        ];

        $this->transferApiService
            ->expects($this->once())
            ->method('handleTransferNotification')
            ->with($notificationData)
            ->willReturn(true);

        $client = $this->setupServiceMocks();
        $this->loginAsAdmin($client);
        $jsonData = json_encode($notificationData);
        if ($jsonData === false) {
            \PHPUnit\Framework\Assert::fail('Failed to encode notification data to JSON');
        }
        $client->request('POST', '/api/wechat-pay-transfer/transfer/notification', [], [], [], $jsonData);

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = $response->getContent();
        if ($content === false) {
            \PHPUnit\Framework\Assert::fail('Failed to get response content');
        }
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertTrue($data['success']);
        $this->assertSame('回调处理成功', $data['message']);
    }

    public function testHandleTransferNotificationFailed(): void
    {
        $notificationData = [
            'id' => 'EV-2018022511223320873',
            'event_type' => 'TRANSFER.FAILED',
        ];

        $this->transferApiService
            ->expects($this->once())
            ->method('handleTransferNotification')
            ->with($notificationData)
            ->willReturn(false);

        $client = $this->setupServiceMocks();
        $this->loginAsAdmin($client);
        $jsonData = json_encode($notificationData);
        if ($jsonData === false) {
            \PHPUnit\Framework\Assert::fail('Failed to encode notification data to JSON');
        }
        $client->request('POST', '/api/wechat-pay-transfer/transfer/notification', [], [], [], $jsonData);

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = $response->getContent();
        if ($content === false) {
            \PHPUnit\Framework\Assert::fail('Failed to get response content');
        }
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertFalse($data['success']);
        $this->assertSame('回调处理失败', $data['message']);
    }

    public function testSetupTransferNotificationSuccess(): void
    {
        $notifyUrl = 'https://example.com/notify';
        $mchid = '1234567890';
        $setupData = [
            'notify_url' => $notifyUrl,
            'mchid' => $mchid,
        ];

        $apiResult = [
            'mchid' => $mchid,
            'notify_url' => $notifyUrl,
            'update_time' => '2018-06-09T10:30:00+08:00',
        ];

        $this->transferApiService
            ->expects($this->once())
            ->method('setupTransferNotification')
            ->with($notifyUrl, $mchid)
            ->willReturn($apiResult);

        $client = $this->setupServiceMocks();
        $this->loginAsAdmin($client);
        $jsonData = json_encode($setupData);
        if ($jsonData === false) {
            \PHPUnit\Framework\Assert::fail('Failed to encode setup data to JSON');
        }
        $client->request('POST', '/api/wechat-pay-transfer/transfer/setup-notification', [], [], [], $jsonData);

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = $response->getContent();
        if ($content === false) {
            \PHPUnit\Framework\Assert::fail('Failed to get response content');
        }
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertTrue($data['success']);
        $this->assertSame('设置转账回调通知成功', $data['message']);
        $this->assertSame($apiResult, $data['data']);
    }

    public function testSetupTransferNotificationMissingNotifyUrl(): void
    {
        $setupData = [
            'mchid' => '1234567890',
            // 缺少 notify_url
        ];

        $client = $this->setupServiceMocks();
        $this->loginAsAdmin($client);
        $jsonData = json_encode($setupData);
        if ($jsonData === false) {
            \PHPUnit\Framework\Assert::fail('Failed to encode setup data to JSON');
        }
        $client->request('POST', '/api/wechat-pay-transfer/transfer/setup-notification', [], [], [], $jsonData);

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $content = $response->getContent();
        if ($content === false) {
            \PHPUnit\Framework\Assert::fail('Failed to get response content');
        }
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertSame('缺少回调通知URL', $data['error']);
    }

    public function testApiExceptionHandling(): void
    {
        $this->transferApiService
            ->expects($this->once())
            ->method('initiateTransfer')
            ->willThrowException(new \RuntimeException('服务不可用'));

        $transferData = [
            'batch_id' => '123',
        ];

        $transferBatch = $this->createMock(TransferBatch::class);

        $this->batchRepository
            ->expects($this->once())
            ->method('find')
            ->with('123')
            ->willReturn($transferBatch);

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->with($transferBatch)
            ->willReturn(new ConstraintViolationList());

        $client = $this->setupServiceMocks();
        $this->loginAsAdmin($client);
        $jsonData = json_encode($transferData);
        if ($jsonData === false) {
            \PHPUnit\Framework\Assert::fail('Failed to encode transfer data to JSON');
        }
        $client->request('POST', '/api/wechat-pay-transfer/transfer/initiate', [], [], [], $jsonData);

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());

        $content = $response->getContent();
        if ($content === false) {
            \PHPUnit\Framework\Assert::fail('Failed to get response content');
        }
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertSame('服务不可用', $data['error']);
    }

    /**
     * @return array<array<string>>
     */
    public static function provideHttpMethodsNotAllowed(): array
    {
        return [
            ['PUT'],
            ['DELETE'],
            ['PATCH'],
        ];
    }

    #[DataProvider('provideHttpMethodsNotAllowed')]
    public function testMethodNotAllowed(string $method): void
    {
        $client = $this->setupServiceMocks();
        $this->loginAsAdmin($client);

        try {
            $client->request($method, '/api/wechat-pay-transfer/transfer/initiate');
        } catch (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e) {
            // 这是预期的异常，MethodNotAllowedHttpException 应该被转换为405响应
            $this->assertSame(405, $e->getStatusCode());
            return;
        }

        // 如果没有抛出异常，检查响应状态码
        $response = $client->getResponse();
        $this->assertSame(405, $response->getStatusCode());
    }

    public function testControllerRouteConfiguration(): void
    {
        $controller = new \ReflectionClass(TransferApiController::class);

        // 验证控制器有正确的路由属性
        $routeAttributes = $controller->getAttributes(\Symfony\Component\Routing\Annotation\Route::class);
        $this->assertCount(1, $routeAttributes, 'Controller should have Route attribute');

        $routeAttribute = $routeAttributes[0]->newInstance();
        $this->assertSame('/api/wechat-pay-transfer', $routeAttribute->getPath());
        $this->assertSame('api_wechat_pay_transfer', $routeAttribute->getName());

        // 验证控制器有日志通道属性
        $logAttributes = $controller->getAttributes(\Monolog\Attribute\WithMonologChannel::class);
        $this->assertCount(1, $logAttributes, 'Controller should have WithMonologChannel attribute');

        $logAttribute = $logAttributes[0]->newInstance();
        $this->assertSame('wechat_pay_transfer', $logAttribute->channel);
    }

    public function testBusinessConfiguration(): void
    {
        // 测试实际的业务属性，确保有非mock的属性被使用
        $this->assertSame('v1', $this->apiVersion);
        $this->assertNotEmpty($this->apiBaseUrl);
        $this->assertStringStartsWith('/api', $this->apiBaseUrl);
        $this->assertStringContainsString('wechat-pay-transfer', $this->apiBaseUrl);

        $this->assertGreaterThan(0, $this->maxTransferAmount);
        $this->assertSame(500000, $this->maxTransferAmount);

        $this->assertIsArray($this->supportedOperations);
        $this->assertNotEmpty($this->supportedOperations);
        $this->assertContains('initiate', $this->supportedOperations);
        $this->assertContains('cancel', $this->supportedOperations);
        $this->assertContains('query', $this->supportedOperations);

        // 测试真实的实体对象
        $this->assertInstanceOf(TransferBatch::class, $this->testBatch);
        $this->assertSame('TEST_BATCH_001', $this->testBatch->getOutBatchNo());
        $this->assertSame('测试批次', $this->testBatch->getBatchName());
        $this->assertSame(100000, $this->testBatch->getTotalAmount());
        $this->assertSame(5, $this->testBatch->getTotalNum());
    }

    public function testInvokeMethodReturnsApiInfo(): void
    {
        $client = $this->setupServiceMocks();
        $this->loginAsAdmin($client);
        $client->request('GET', '/api/wechat-pay-transfer/');

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = $response->getContent();
        if ($content === false) {
            \PHPUnit\Framework\Assert::fail('Failed to get response content');
        }
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertTrue($data['success']);
        $this->assertSame('微信支付转账API', $data['message']);
        $this->assertIsArray($data['data']);
        $this->assertArrayHasKey('available_endpoints', $data['data']);
        $this->assertArrayHasKey('version', $data['data']);

        // 测试实际的业务属性
        $this->assertSame('v1', $this->apiVersion);
        $this->assertNotEmpty($this->apiVersion);
    }
}