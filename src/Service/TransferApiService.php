<?php

declare(strict_types=1);

namespace WechatPayTransferBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use WechatPayBundle\Service\WechatPayClient;
use WechatPayTransferBundle\Entity\TransferBatch;
use WechatPayTransferBundle\Entity\TransferDetail;
use WechatPayTransferBundle\Enum\TransferBatchStatus;
use WechatPayTransferBundle\Enum\TransferDetailStatus;
use WechatPayTransferBundle\Repository\TransferBatchRepository;
use WechatPayTransferBundle\Repository\TransferDetailRepository;

/**
 * 微信支付转账API服务类
 * 
 * 提供微信支付商家转账相关的API调用功能，包括发起转账、查询转账、撤销转账等操作。
 * 
 * @see https://pay.weixin.qq.com/doc/v3/merchant/4012711988
 */
#[WithMonologChannel(channel: 'wechat_pay_transfer')]
#[Autoconfigure(public: true)]
class TransferApiService
{
    private HttpClientInterface $httpClient;
    private EntityManagerInterface $entityManager;

    /**
     */
    private TransferBatchRepository $batchRepository;

    /**
     */
    private TransferDetailRepository $detailRepository;

    private LoggerInterface $logger;

    /**
     * @param TransferBatchRepository $batchRepository
     * @param TransferDetailRepository $detailRepository
     */
    public function __construct(
        WechatPayClient $wechatPayClient,
        EntityManagerInterface $entityManager,
        TransferBatchRepository $batchRepository,
        TransferDetailRepository $detailRepository,
        LoggerInterface $logger
    ) {
        $this->httpClient = $wechatPayClient->getClient();
        $this->entityManager = $entityManager;
        $this->batchRepository = $batchRepository;
        $this->detailRepository = $detailRepository;
        $this->logger = $logger;
    }

    /**
     * 发起转账
     * 调用微信支付官方API发起批量转账请求
     *
     * @param TransferBatch $transferBatch 转账批次
     * @return array<string, mixed> API响应结果
     * @throws \Exception 转账失败时抛出异常
     *
     * @see https://pay.weixin.qq.com/doc/v3/merchant/4012716452
     */
    public function initiateTransfer(TransferBatch $transferBatch): array
    {
        try {
            $this->validateTransferBatch($transferBatch);
            $requestData = $this->buildTransferRequestData($transferBatch);
            $responseData = $this->makeApiRequest('/v3/fund-app/mch-transfer/transfer-bills', $requestData);

            $this->updateBatchAfterInitiate($transferBatch, $responseData);
            $this->entityManager->flush();

            $this->logTransferSuccess($transferBatch, $responseData);

            return $responseData;

        } catch (\Exception $e) {
            $this->logTransferError($transferBatch, $e);
            throw $e;
        }
    }

    /**
     * APP调起用户确认收款
     *
     * 生成APP调起用户确认收款的参数，供APP端调用微信支付SDK
     *
     * @param TransferDetail $transferDetail 转账明细
     * @return array<string, string> APP调用参数
     * @throws \Exception 参数生成失败时抛出异常
     *
     * @see https://pay.weixin.qq.com/doc/v3/merchant/4012716452
     */
    public function generateAppConfirmParameters(TransferDetail $transferDetail): array
    {
        if ($transferDetail->getDetailId() === null || $transferDetail->getDetailId() === '') {
            throw new \InvalidArgumentException('转账明细必须有微信明细单号');
        }

        $batch = $transferDetail->getBatch();
        assert($batch instanceof TransferBatch);
        $merchant = $batch->getMerchant();
        if ($merchant === null) {
            throw new \InvalidArgumentException('转账批次必须关联商户');
        }

        // 生成APP调用参数
        $appId = $transferDetail->getBatch()?->getAppId();
        if ($appId === null) {
            throw new \InvalidArgumentException('转账明细必须关联到设置了应用ID的批次');
        }
        $parameters = [
            'appid' => $appId,
            'package' => "transfer_detail_id={$transferDetail->getDetailId()}",
            'timestamp' => (string)time(),
            'sign_type' => 'RSA',
        ];

        // 这里需要添加签名生成逻辑
        // $parameters['sign'] = $this->generateSignature($parameters);

        $this->logger->info('生成APP确认参数', [
            'detail_id' => $transferDetail->getId(),
            'wechat_detail_id' => $transferDetail->getDetailId(),
        ]);

        return $parameters;
    }

    /**
     * JSAPI调起用户确认收款
     *
     * 生成JSAPI调起用户确认收款的参数，供H5页面调用微信支付JSAPI
     *
     * @param TransferDetail $transferDetail 转账明细
     * @param string $openid 用户openid
     * @return array<string, string> JSAPI调用参数
     * @throws \Exception 参数生成失败时抛出异常
     *
     * @see https://pay.weixin.qq.com/doc/v3/merchant/4012716452
     */
    public function generateJsApiConfirmParameters(TransferDetail $transferDetail, string $openid): array
    {
        if ($transferDetail->getDetailId() === null || $transferDetail->getDetailId() === '') {
            throw new \InvalidArgumentException('转账明细必须有微信明细单号');
        }

        $batch = $transferDetail->getBatch();
        assert($batch instanceof TransferBatch);
        $merchant = $batch->getMerchant();
        if ($merchant === null) {
            throw new \InvalidArgumentException('转账批次必须关联商户');
        }

        // 生成JSAPI调用参数
        $appId = $batch->getAppId();
        if ($appId === null) {
            throw new \InvalidArgumentException('转账批次必须设置应用ID');
        }
        $parameters = [
            'appId' => $appId,
            'timeStamp' => (string)time(),
            'nonceStr' => uniqid('transfer_', true),
            'package' => "transfer_detail_id={$transferDetail->getDetailId()}",
            'signType' => 'RSA',
            'openid' => $openid,
        ];

        // 这里需要添加签名生成逻辑
        // $parameters['paySign'] = $this->generateJsApiSignature($parameters);

        $this->logger->info('生成JSAPI确认参数', [
            'detail_id' => $transferDetail->getId(),
            'wechat_detail_id' => $transferDetail->getDetailId(),
            'openid' => $openid,
        ]);

        return $parameters;
    }

    /**
     * 撤销转账
     *
     * 调用微信支付官方API撤销指定的转账批次
     *
     * @param string $outBatchNo 商户批次单号
     * @return array<string, mixed> API响应结果
     * @throws \Exception 撤销失败时抛出异常
     *
     * @see https://pay.weixin.qq.com/doc/v3/merchant/4012716452
     */
    public function cancelTransfer(string $outBatchNo): array
    {
        try {
            // 调用微信支付官方API撤销转账
            $response = $this->httpClient->request('POST', "/v3/fund-app/mch-transfer/transfer-bills/out-bill-no/{$outBatchNo}/cancel");

            $statusCode = $response->getStatusCode();
            $responseData = $response->toArray(false);
            /** @var array<string, mixed> $responseData */

            if ($statusCode !== Response::HTTP_OK) {
                $errorMessage = $responseData['message'] ?? '未知错误';
                assert(is_string($errorMessage));
                throw new \RuntimeException("撤销转账失败: " . $errorMessage);
            }

            // 更新本地批次状态
            $transferBatch = $this->batchRepository->findOneBy(['outBatchNo' => $outBatchNo]);

            if ($transferBatch !== null) {
                $transferBatch->setBatchStatus(TransferBatchStatus::CLOSED);
                
                // 更新所有明细状态为失败
                foreach ($transferBatch->getDetails() as $detail) {
                    $detail->setDetailStatus(TransferDetailStatus::FAIL);
                }
                
                $this->entityManager->flush();
            }

            $this->logger->info('撤销转账成功', [
                'out_batch_no' => $outBatchNo,
                'response' => $responseData,
            ]);

            return $responseData;

        } catch (\Exception $e) {
            $this->logger->error('撤销转账失败', [
                'out_batch_no' => $outBatchNo,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * 商户单号查询转账单
     *
     * 调用微信支付官方API通过商户批次单号查询转账详情
     *
     * @param string $outBatchNo 商户批次单号
     * @param bool $needQueryDetail 是否需要查询明细信息
     * @return array<string, mixed> 查询结果
     * @throws \Exception 查询失败时抛出异常
     *
     * @see https://pay.weixin.qq.com/doc/v3/merchant/4012716452
     */
    public function queryTransferByOutBatchNo(string $outBatchNo, bool $needQueryDetail = false): array
    {
        try {
            // 构建查询URL
            $url = "/v3/fund-app/mch-transfer/transfer-bills/out-bill-no/{$outBatchNo}";
            if ($needQueryDetail) {
                $url .= '?need_query_detail=true';
            }

            // 调用微信支付官方API查询转账
            $response = $this->httpClient->request('GET', $url);
            $responseData = $response->toArray(false);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== Response::HTTP_OK) {
                $errorMessage = $responseData['message'] ?? '未知错误';
                assert(is_string($errorMessage));
                throw new \RuntimeException("查询转账失败: " . $errorMessage);
            }

            /** @var array<string, mixed> $responseData */
            // 更新本地数据
            $this->updateLocalTransferData($responseData);

            $this->logger->info('查询转账成功', [
                'out_batch_no' => $outBatchNo,
                'need_query_detail' => $needQueryDetail,
                'response' => $responseData,
            ]);

            return $responseData;

        } catch (\Exception $e) {
            $this->logger->error('查询转账失败', [
                'out_batch_no' => $outBatchNo,
                'need_query_detail' => $needQueryDetail,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * 微信单号查询转账单
     *
     * 调用微信支付官方API通过微信批次单号查询转账详情
     *
     * @param string $batchId 微信批次单号
     * @param bool $needQueryDetail 是否需要查询明细信息
     * @return array<string, mixed> 查询结果
     * @throws \Exception 查询失败时抛出异常
     *
     * @see https://pay.weixin.qq.com/doc/v3/merchant/4012716452
     */
    public function queryTransferByBatchId(string $batchId, bool $needQueryDetail = false): array
    {
        try {
            // 构建查询URL
            $url = "/v3/fund-app/mch-transfer/transfer-bills/transfer-bill-no/{$batchId}";
            if ($needQueryDetail) {
                $url .= '?need_query_detail=true';
            }

            // 调用微信支付官方API查询转账
            $response = $this->httpClient->request('GET', $url);
            $responseData = $response->toArray(false);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== Response::HTTP_OK) {
                $errorMessage = $responseData['message'] ?? '未知错误';
                assert(is_string($errorMessage));
                throw new \RuntimeException("查询转账失败: " . $errorMessage);
            }

            /** @var array<string, mixed> $responseData */
            // 更新本地数据
            $this->updateLocalTransferData($responseData);

            $this->logger->info('查询转账成功', [
                'batch_id' => $batchId,
                'need_query_detail' => $needQueryDetail,
                'response' => $responseData,
            ]);

            return $responseData;

        } catch (\Exception $e) {
            $this->logger->error('查询转账失败', [
                'batch_id' => $batchId,
                'need_query_detail' => $needQueryDetail,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * 上架转账回调通知
     *
     * 调用微信支付官方API设置转账回调通知URL
     *
     * @param string $notifyUrl 回调通知URL
     * @param string|null $mchid 商户号（可选）
     * @return array<string, mixed> API响应结果
     * @throws \Exception 设置失败时抛出异常
     *
     * @see https://pay.weixin.qq.com/doc/v3/merchant/4012716452
     */
    public function setupTransferNotification(string $notifyUrl, ?string $mchid = null): array
    {
        try {
            // 构建请求参数
            $requestData = [
                'main_appid' => null, // 主商户appid，如果有子商户需要设置
                'notify_url' => $notifyUrl,
            ];

            if ($mchid !== null && $mchid !== '') {
                $requestData['mchid'] = $mchid;
            }

            // 调用微信支付官方API设置回调通知
            $response = $this->httpClient->request('POST', '/v3/fund-app/mch-transfer/transfer-bill-receipt-notify', [
                'json' => $requestData,
            ]);

            $statusCode = $response->getStatusCode();
            $responseData = $response->toArray(false);
            /** @var array<string, mixed> $responseData */

            if ($statusCode !== Response::HTTP_OK) {
                $errorMessage = $responseData['message'] ?? '未知错误';
                assert(is_string($errorMessage));
                throw new \RuntimeException("设置转账回调通知失败: " . $errorMessage);
            }

            $this->logger->info('设置转账回调通知成功', [
                'notify_url' => $notifyUrl,
                'mchid' => $mchid,
                'response' => $responseData,
            ]);

            return $responseData;

        } catch (\Exception $e) {
            $this->logger->error('设置转账回调通知失败', [
                'notify_url' => $notifyUrl,
                'mchid' => $mchid,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * 处理转账回调通知
     *
     * 处理微信支付发送的转账状态变更通知
     *
     * @param array<string, mixed> $notificationData 回调通知数据
     * @return bool 处理是否成功
     *
     * @see https://pay.weixin.qq.com/doc/v3/merchant/4012716452
     */
    public function handleTransferNotification(array $notificationData): bool
    {
        try {
            $decryptedData = $this->validateAndDecryptNotification($notificationData);
            $transferBatch = $this->findTransferBatchByNotification($decryptedData);

            $this->updateTransferStatusFromNotification($transferBatch, $decryptedData);
            $this->entityManager->flush();

            $this->logNotificationSuccess($notificationData, $decryptedData);

            return true;

        } catch (\Exception $e) {
            $this->logNotificationError($notificationData, $e);
            return false;
        }
    }

    /**
     * 构建转账明细列表
     * @return array<array<string, mixed>>
     */
    private function buildTransferDetails(TransferBatch $transferBatch): array
    {
        $details = [];
        
        foreach ($transferBatch->getDetails() as $detail) {
            $detailData = [
                'out_detail_no' => $detail->getOutDetailNo(),
                'transfer_amount' => $detail->getTransferAmount(),
                'transfer_remark' => $detail->getTransferRemark(),
                'openid' => $detail->getOpenid(),
            ];

            // 用户姓名是可选的
            if ($detail->getUserName() !== null && $detail->getUserName() !== '') {
                $detailData['user_name'] = $detail->getUserName();
            }

            $details[] = $detailData;
        }

        return $details;
    }

    /**
     * 根据查询结果更新本地转账数据
     * @param array<string, mixed> $responseData
     */
    private function updateLocalTransferData(array $responseData): void
    {
        $transferBatch = $this->findTransferBatchFromResponse($responseData);
        if ($transferBatch === null) {
            return;
        }

        $this->updateBatchInfo($transferBatch, $responseData);
        $this->updateTransferDetails($transferBatch, $responseData);

        $this->entityManager->flush();
    }

    /**
     * 从响应数据中查找转账批次
     * @param array<string, mixed> $responseData
     */
    private function findTransferBatchFromResponse(array $responseData): ?TransferBatch
    {
        $outBatchNo = $responseData['out_batch_no'] ?? null;
        if ($outBatchNo === null || $outBatchNo === '') {
            return null;
        }

        return $this->batchRepository->findOneBy(['outBatchNo' => $outBatchNo]);
    }

    /**
     * 更新批次基本信息
     * @param array<string, mixed> $responseData
     */
    private function updateBatchInfo(TransferBatch $transferBatch, array $responseData): void
    {
        // 更新批次ID
        if (isset($responseData['batch_id'])) {
            $batchId = $responseData['batch_id'];
            assert(is_string($batchId));
            $transferBatch->setBatchId($batchId);
        }

        // 更新批次状态
        $this->updateBatchStatus($transferBatch, $responseData);
    }

    /**
     * 更新批次状态
     * @param array<string, mixed> $responseData
     */
    private function updateBatchStatus(TransferBatch $transferBatch, array $responseData): void
    {
        if (!isset($responseData['batch_status'])) {
            return;
        }

        $batchStatus = $responseData['batch_status'];
        assert(is_string($batchStatus) || is_int($batchStatus));

        try {
            $transferBatch->setBatchStatus(TransferBatchStatus::from($batchStatus));
        } catch (\ValueError $e) {
            $this->logger->warning('未知的批次状态', [
                'batch_status' => $batchStatus,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 更新转账明细信息
     * @param array<string, mixed> $responseData
     */
    private function updateTransferDetails(TransferBatch $transferBatch, array $responseData): void
    {
        $detailList = $responseData['transfer_detail_list'] ?? [];
        if (!is_array($detailList)) {
            return;
        }

        foreach ($detailList as $detailData) {
            assert(is_array($detailData));
            /** @var array<string, mixed> $detailData */
            $this->updateSingleTransferDetail($transferBatch, $detailData);
        }
    }

    /**
     * 更新单个转账明细
     * @param array<string, mixed> $detailData
     */
    private function updateSingleTransferDetail(TransferBatch $transferBatch, array $detailData): void
    {
        $outDetailNo = $detailData['out_detail_no'] ?? null;
        if ($outDetailNo === null || $outDetailNo === '') {
            return;
        }

        $detail = $this->detailRepository->findOneBy(['outDetailNo' => $outDetailNo, 'batch' => $transferBatch]);
        if ($detail === null) {
            return;
        }

        $this->updateDetailId($detail, $detailData);
        $this->updateDetailStatus($detail, $detailData);
    }

    /**
     * 更新明细ID
     * @param array<string, mixed> $detailData
     */
    private function updateDetailId(TransferDetail $detail, array $detailData): void
    {
        if (!isset($detailData['detail_id'])) {
            return;
        }

        $detailId = $detailData['detail_id'];
        assert(is_string($detailId));
        $detail->setDetailId($detailId);
    }

    /**
     * 更新明细状态
     * @param array<string, mixed> $detailData
     */
    private function updateDetailStatus(TransferDetail $detail, array $detailData): void
    {
        if (!isset($detailData['detail_status'])) {
            return;
        }

        $detailStatus = $detailData['detail_status'];
        assert(is_string($detailStatus) || is_int($detailStatus));

        try {
            $detail->setDetailStatus(TransferDetailStatus::from($detailStatus));
        } catch (\ValueError $e) {
            $this->logger->warning('未知的明细状态', [
                'detail_status' => $detailStatus,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 根据回调通知更新转账状态
     * @param array<string, mixed> $decryptedData
     */
    private function updateTransferStatusFromNotification(TransferBatch $transferBatch, array $decryptedData): void
    {
        $this->updateBatchStatus($transferBatch, $decryptedData);
        $this->updateTransferDetails($transferBatch, $decryptedData);
    }

    /**
     * 验证转账批次
     */
    private function validateTransferBatch(TransferBatch $transferBatch): void
    {
        $merchant = $transferBatch->getMerchant();
        if ($merchant === null) {
            throw new \InvalidArgumentException('转账批次必须关联商户');
        }

        $appId = $transferBatch->getAppId();
        if ($appId === null) {
            throw new \InvalidArgumentException('转账批次必须设置应用ID');
        }
    }

    /**
     * 构建转账请求数据
     * @return array<string, mixed>
     */
    private function buildTransferRequestData(TransferBatch $transferBatch): array
    {
        $requestData = [
            'appid' => $transferBatch->getAppId(),
            'out_batch_no' => $transferBatch->getOutBatchNo(),
            'batch_name' => $transferBatch->getBatchName(),
            'batch_remark' => $transferBatch->getBatchRemark(),
            'total_amount' => $transferBatch->getTotalAmount(),
            'total_num' => $transferBatch->getTotalNum(),
            'transfer_detail_list' => $this->buildTransferDetails($transferBatch),
        ];

        // 如果有转账场景ID，添加到请求中
        if ($transferBatch->getTransferSceneId() !== null && $transferBatch->getTransferSceneId() !== '') {
            $requestData['transfer_scene_id'] = $transferBatch->getTransferSceneId();
        }

        return $requestData;
    }

    /**
     * 发起API请求
     * @param array<string, mixed> $requestData
     * @return array<string, mixed>
     */
    private function makeApiRequest(string $endpoint, array $requestData): array
    {
        $response = $this->httpClient->request('POST', $endpoint, [
            'json' => $requestData,
        ]);

        $statusCode = $response->getStatusCode();
        $responseData = $response->toArray(false);
        /** @var array<string, mixed> $responseData */

        if ($statusCode !== Response::HTTP_OK) {
            $errorMessage = $responseData['message'] ?? '未知错误';
            assert(is_string($errorMessage));
            throw new \RuntimeException("API请求失败: " . $errorMessage);
        }

        return $responseData;
    }

    /**
     * 更新发起转账后的批次信息
     * @param array<string, mixed> $responseData
     */
    private function updateBatchAfterInitiate(TransferBatch $transferBatch, array $responseData): void
    {
        $transferBatch->setBatchStatus(TransferBatchStatus::PROCESSING);

        if (isset($responseData['batch_id'])) {
            $batchId = $responseData['batch_id'];
            assert(is_string($batchId));
            $transferBatch->setBatchId($batchId);
        }

        // 更新明细状态
        foreach ($transferBatch->getDetails() as $detail) {
            $detail->setDetailStatus(TransferDetailStatus::WAIT_PAY);
        }
    }

    /**
     * 验证并解密通知数据
     * @param array<string, mixed> $notificationData
     * @return array<string, mixed>
     */
    private function validateAndDecryptNotification(array $notificationData): array
    {
        $event = $notificationData['event'] ?? null;
        $resource = $notificationData['resource'] ?? [];

        assert(is_array($resource) || is_countable($resource));
        if ($event === null || $event === '' || count($resource) === 0) {
            throw new \InvalidArgumentException('回调通知数据格式错误');
        }

        assert(is_array($resource));
        /** @var array<string, mixed> $resource */
        return $this->decryptNotificationData($resource);
    }

    /**
     * 根据通知数据查找转账批次
     * @param array<string, mixed> $decryptedData
     */
    private function findTransferBatchByNotification(array $decryptedData): TransferBatch
    {
        $outBatchNo = $decryptedData['out_batch_no'] ?? null;
        if ($outBatchNo === null || $outBatchNo === '') {
            throw new \InvalidArgumentException('回调数据中缺少商户批次单号');
        }
        assert(is_string($outBatchNo));

        $transferBatch = $this->batchRepository->findOneBy(['outBatchNo' => $outBatchNo]);
        if ($transferBatch === null) {
            throw new \RuntimeException("未找到批次号: {$outBatchNo} 对应的转账批次");
        }

        return $transferBatch;
    }

    /**
     * 记录转账成功日志
     * @param array<string, mixed> $responseData
     */
    private function logTransferSuccess(TransferBatch $transferBatch, array $responseData): void
    {
        $this->logger->info('发起转账成功', [
            'batch_id' => $transferBatch->getId(),
            'out_batch_no' => $transferBatch->getOutBatchNo(),
            'response' => $responseData,
        ]);
    }

    /**
     * 记录转账失败日志
     */
    private function logTransferError(TransferBatch $transferBatch, \Exception $e): void
    {
        $this->logger->error('发起转账失败', [
            'batch_id' => $transferBatch->getId(),
            'out_batch_no' => $transferBatch->getOutBatchNo(),
            'error' => $e->getMessage(),
        ]);
    }

    /**
     * 记录通知处理成功日志
     * @param array<string, mixed> $notificationData
     * @param array<string, mixed> $decryptedData
     */
    private function logNotificationSuccess(array $notificationData, array $decryptedData): void
    {
        $event = $notificationData['event'] ?? null;
        $outBatchNo = $decryptedData['out_batch_no'] ?? null;

        $this->logger->info('处理转账回调成功', [
            'event' => $event,
            'out_batch_no' => $outBatchNo,
            'decrypted_data' => $decryptedData,
        ]);
    }

    /**
     * 记录通知处理失败日志
     * @param array<string, mixed> $notificationData
     */
    private function logNotificationError(array $notificationData, \Exception $e): void
    {
        $this->logger->error('处理转账回调失败', [
            'notification_data' => $notificationData,
            'error' => $e->getMessage(),
        ]);
    }

    /**
     * 解密回调通知数据
     * @param array<string, mixed> $resource
     * @return array<string, mixed>
     */
    private function decryptNotificationData(array $resource): array
    {
        // 这里需要实现微信支付回调数据的解密逻辑
        // 具体实现取决于微信支付客户端的解密方法
        // 暂时返回原始数据，实际使用时需要实现解密

        // 示例解密逻辑（需要根据实际的wechat-pay-bundle实现）:
        // $cipherText = $resource['ciphertext'];
        // $nonce = $resource['nonce'];
        // $associatedData = $resource['associated_data'];
        //
        // return $this->wechatPayClient->decrypt($cipherText, $nonce, $associatedData);

        return $resource;
    }
}
