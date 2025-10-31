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
use WechatPayTransferBundle\Entity\TransferReceipt;
use WechatPayTransferBundle\Enum\TransferReceiptStatus;
use WechatPayTransferBundle\Repository\TransferBatchRepository;
use WechatPayTransferBundle\Repository\TransferDetailRepository;
use WechatPayTransferBundle\Repository\TransferReceiptRepository;

/**
 * 微信支付电子回单API服务类
 * 
 * 提供微信支付商家转账电子回单相关的API调用功能，包括申请回单、查询回单、下载回单等操作。
 * 
 * @see https://pay.weixin.qq.com/doc/v3/merchant/4012711988
 */
#[WithMonologChannel(channel: 'wechat_pay_transfer')]
#[Autoconfigure(public: true)]
class TransferReceiptApiService
{
    private HttpClientInterface $httpClient;
    private EntityManagerInterface $entityManager;

    /**
     */
    private TransferBatchRepository $batchRepository;

    /**
     */
    private TransferDetailRepository $detailRepository;

    /**
     */
    private TransferReceiptRepository $receiptRepository;

    private LoggerInterface $logger;

    /**
     * @param TransferBatchRepository $batchRepository
     * @param TransferDetailRepository $detailRepository
     * @param TransferReceiptRepository $receiptRepository
     */
    public function __construct(
        WechatPayClient $wechatPayClient,
        EntityManagerInterface $entityManager,
        TransferBatchRepository $batchRepository,
        TransferDetailRepository $detailRepository,
        TransferReceiptRepository $receiptRepository,
        LoggerInterface $logger
    ) {
        $this->httpClient = $wechatPayClient->getClient();
        $this->entityManager = $entityManager;
        $this->batchRepository = $batchRepository;
        $this->detailRepository = $detailRepository;
        $this->receiptRepository = $receiptRepository;
        $this->logger = $logger;
    }

    /**
     * 商户单号申请电子回单
     *
     * 调用微信支付官方API通过商户批次单号或明细单号申请电子回单
     *
     * @param string $outBatchNo 商户批次单号
     * @param string|null $outDetailNo 商户明细单号（可选）
     * @return TransferReceipt 电子回单实体
     * @throws \Exception 申请失败时抛出异常
     *
     * @see https://pay.weixin.qq.com/doc/v3/merchant/4012716452
     */
    public function applyReceiptByOutBatchNo(string $outBatchNo, ?string $outDetailNo = null): TransferReceipt
    {
        $identifier = new ReceiptIdentifier($outBatchNo, null, $outDetailNo, null);
        return $this->applyReceiptByIdentifier($identifier);
    }

    /**
     * 微信单号申请电子回单
     *
     * 调用微信支付官方API通过微信批次单号或明细单号申请电子回单
     *
     * @param string $batchId 微信批次单号
     * @param string|null $detailId 微信明细单号（可选）
     * @return TransferReceipt 电子回单实体
     * @throws \Exception 申请失败时抛出异常
     *
     * @see https://pay.weixin.qq.com/doc/v3/merchant/4012716452
     */
    public function applyReceiptByBatchId(string $batchId, ?string $detailId = null): TransferReceipt
    {
        $identifier = new ReceiptIdentifier(null, $batchId, null, $detailId);
        return $this->applyReceiptByIdentifier($identifier);
    }

    /**
     * 商户单号查询电子回单
     *
     * 调用微信支付官方API通过商户批次单号或明细单号查询电子回单状态
     *
     * @param string $outBatchNo 商户批次单号
     * @param string|null $outDetailNo 商户明细单号（可选）
     * @return TransferReceipt|null 电子回单实体，未找到时返回null
     * @throws \Exception 查询失败时抛出异常
     *
     * @see https://pay.weixin.qq.com/doc/v3/merchant/4012716452
     */
    public function queryReceiptByOutBatchNo(string $outBatchNo, ?string $outDetailNo = null): ?TransferReceipt
    {
        $identifier = new ReceiptIdentifier($outBatchNo, null, $outDetailNo, null);
        return $this->queryReceiptByIdentifier($identifier);
    }

    /**
     * 微信单号查询电子回单
     *
     * 调用微信支付官方API通过微信批次单号或明细单号查询电子回单状态
     *
     * @param string $batchId 微信批次单号
     * @param string|null $detailId 微信明细单号（可选）
     * @return TransferReceipt|null 电子回单实体，未找到时返回null
     * @throws \Exception 查询失败时抛出异常
     *
     * @see https://pay.weixin.qq.com/doc/v3/merchant/4012716452
     */
    public function queryReceiptByBatchId(string $batchId, ?string $detailId = null): ?TransferReceipt
    {
        $identifier = new ReceiptIdentifier(null, $batchId, null, $detailId);
        return $this->queryReceiptByIdentifier($identifier);
    }

    /**
     * 下载电子回单
     * 
     * 通过官方API返回的下载URL下载电子回单文件
     * 
     * @param string $downloadUrl 下载URL
     * @return string 文件内容
     * @throws \Exception 下载失败时抛出异常
     * 
     * @see https://pay.weixin.qq.com/doc/v3/merchant/4012716452
     */
    public function downloadReceipt(string $downloadUrl): string
    {
        try {
            // 使用微信支付客户端下载回单文件
            $response = $this->httpClient->request('GET', $downloadUrl);
            $statusCode = $response->getStatusCode();

            if ($statusCode !== Response::HTTP_OK) {
                throw new \RuntimeException("下载电子回单失败: HTTP {$statusCode}");
            }

            $fileContent = $response->getContent();

            $this->logger->info('下载电子回单成功', [
                'download_url' => $downloadUrl,
                'file_size' => strlen($fileContent),
            ]);

            return $fileContent;

        } catch (\Exception $e) {
            $this->logger->error('下载电子回单失败', [
                'download_url' => $downloadUrl,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * 批量处理电子回单申请
     *
     * 为指定的转账批次批量申请电子回单（调用官方API）
     *
     * @param TransferBatch $transferBatch 转账批次
     * @return array{batch: TransferReceipt, details: list<TransferReceipt>} 申请结果
     * @throws \Exception 申请失败时抛出异常
     */
    public function batchApplyReceipts(TransferBatch $transferBatch): array
    {
        return $this->processBatchReceipts($transferBatch, function (ReceiptIdentifier $identifier) {
            return $this->applyReceiptByIdentifier($identifier);
        });
    }

    /**
     * 批量处理回单的通用模板方法
     * @param callable(ReceiptIdentifier): TransferReceipt $processor
     * @return array{batch: TransferReceipt, details: list<TransferReceipt>}
     */
    private function processBatchReceipts(TransferBatch $transferBatch, callable $processor): array
    {
        $results = [];

        try {
            // 为批次申请回单
            $batchIdentifier = new ReceiptIdentifier($transferBatch->getOutBatchNo());
            $batchReceipt = $processor($batchIdentifier);
            $results['batch'] = $batchReceipt;

            // 为每个明细申请回单
            $detailReceipts = [];
            foreach ($transferBatch->getDetails() as $detail) {
                $detailIdentifier = new ReceiptIdentifier(
                    $transferBatch->getOutBatchNo(),
                    null,
                    $detail->getOutDetailNo()
                );
                $detailReceipt = $processor($detailIdentifier);
                $detailReceipts[] = $detailReceipt;
            }
            $results['details'] = $detailReceipts;

            $this->logger->info('批量申请电子回单成功', [
                'batch_id' => $transferBatch->getId(),
                'out_batch_no' => $transferBatch->getOutBatchNo(),
                'detail_count' => count($detailReceipts),
            ]);

        } catch (\Exception $e) {
            $this->logger->error('批量申请电子回单失败', [
                'batch_id' => $transferBatch->getId(),
                'out_batch_no' => $transferBatch->getOutBatchNo(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        return $results;
    }

    /**
     * 通过标识符申请电子回单（核心方法）
     */
    private function applyReceiptByIdentifier(ReceiptIdentifier $identifier): TransferReceipt
    {
        try {
            $requestData = $identifier->buildApplyRequestData();
            $responseData = $this->sendApplyRequest($requestData);

            $transferReceipt = $this->createOrUpdateReceipt($responseData, $identifier->getType());
            $this->setReceiptIdentifiersFromIdentifier($transferReceipt, $identifier);

            $this->entityManager->persist($transferReceipt);
            $this->entityManager->flush();

            $this->logApplySuccessByIdentifier($identifier, $transferReceipt, $responseData);

            return $transferReceipt;

        } catch (\Exception $e) {
            $this->logApplyErrorByIdentifier($identifier, $e);
            throw $e;
        }
    }

    /**
     * 通过标识符查询电子回单（核心方法）
     */
    private function queryReceiptByIdentifier(ReceiptIdentifier $identifier): TransferReceipt
    {
        try {
            $url = $identifier->buildQueryUrl();
            $responseData = $this->sendQueryRequest($url);

            $transferReceipt = $this->loadReceiptForQuery($identifier);
            $this->updateReceiptFromResponse($transferReceipt, $responseData);

            $this->entityManager->persist($transferReceipt);
            $this->entityManager->flush();

            $this->logQuerySuccessByIdentifier($identifier, $transferReceipt, $responseData);

            return $transferReceipt;

        } catch (\Exception $e) {
            $this->logQueryErrorByIdentifier($identifier, $e);
            throw $e;
        }
    }

    /**
     * 发送申请请求
     * @param array<string, mixed> $requestData
     * @return array<string, mixed>
     */
    private function sendApplyRequest(array $requestData): array
    {
        $response = $this->httpClient->request('POST', '/v3/fund-app/mch-transfer/elecsign/out-bill-no', [
            'json' => $requestData,
        ]);

        $statusCode = $response->getStatusCode();
        $responseData = $response->toArray(false);
        /** @var array<string, mixed> $responseData */

        if ($statusCode !== Response::HTTP_OK) {
            $errorMessage = $responseData['message'] ?? '未知错误';
            assert(is_string($errorMessage));
            throw new \RuntimeException("申请电子回单失败: " . $errorMessage);
        }

        return $responseData;
    }

    /**
     * 发送查询请求
     * @return array<string, mixed>
     */
    private function sendQueryRequest(string $url): array
    {
        $response = $this->httpClient->request('GET', $url);
        $responseData = $response->toArray(false);
        /** @var array<string, mixed> $responseData */

        $statusCode = $response->getStatusCode();
        if ($statusCode !== Response::HTTP_OK) {
            $errorMessage = $responseData['message'] ?? '未知错误';
            assert(is_string($errorMessage));
            throw new \RuntimeException("查询电子回单失败: " . $errorMessage);
        }

        return $responseData;
    }

    /**
     * 为查询加载或创建回单实体
     */
    private function loadReceiptForQuery(ReceiptIdentifier $identifier): TransferReceipt
    {
        $transferReceipt = null;

        if ($identifier->isOutBatchNoType()) {
            $transferReceipt = $this->receiptRepository->findOneBy([
                'outBatchNo' => $identifier->getOutBatchNo(),
                'outDetailNo' => $identifier->getOutDetailNo(),
            ]);
        } else {
            $transferReceipt = $this->receiptRepository->findOneBy([
                'batchId' => $identifier->getBatchId(),
                'detailId' => $identifier->getDetailId(),
            ]);
        }

        if ($transferReceipt === null) {
            $transferReceipt = new TransferReceipt();
            $this->setReceiptIdentifiersFromIdentifier($transferReceipt, $identifier);
        }

        assert($transferReceipt instanceof TransferReceipt);
        return $transferReceipt;
    }

    /**
     * 从标识符设置回单标识符
     */
    private function setReceiptIdentifiersFromIdentifier(TransferReceipt $transferReceipt, ReceiptIdentifier $identifier): void
    {
        if ($identifier->getOutBatchNo() !== null) {
            $transferReceipt->setOutBatchNo($identifier->getOutBatchNo());
        }

        if ($identifier->getBatchId() !== null) {
            $transferReceipt->setBatchId($identifier->getBatchId());
        }

        if ($identifier->hasDetailNo()) {
            $transferReceipt->setOutDetailNo($identifier->getOutDetailNo());
        }

        if ($identifier->hasDetailId()) {
            $transferReceipt->setDetailId($identifier->getDetailId());
        }
    }

    /**
     * 记录申请成功日志（通过标识符）
     * @param array<string, mixed> $responseData
     */
    private function logApplySuccessByIdentifier(ReceiptIdentifier $identifier, TransferReceipt $transferReceipt, array $responseData): void
    {
        $logData = array_merge($identifier->getLogData(), [
            'receipt_id' => $transferReceipt->getId(),
            'response' => $responseData,
        ]);

        $this->logger->info('申请电子回单成功', $logData);
    }

    /**
     * 记录申请失败日志（通过标识符）
     */
    private function logApplyErrorByIdentifier(ReceiptIdentifier $identifier, \Exception $e): void
    {
        $logData = array_merge($identifier->getLogData(), [
            'error' => $e->getMessage(),
        ]);

        $this->logger->error('申请电子回单失败', $logData);
    }

    /**
     * 记录查询成功日志（通过标识符）
     * @param array<string, mixed> $responseData
     */
    private function logQuerySuccessByIdentifier(ReceiptIdentifier $identifier, TransferReceipt $transferReceipt, array $responseData): void
    {
        $logData = array_merge($identifier->getLogData(), [
            'receipt_id' => $transferReceipt->getId(),
            'response' => $responseData,
        ]);

        $this->logger->info('查询电子回单成功', $logData);
    }

    /**
     * 记录查询失败日志（通过标识符）
     */
    private function logQueryErrorByIdentifier(ReceiptIdentifier $identifier, \Exception $e): void
    {
        $logData = array_merge($identifier->getLogData(), [
            'error' => $e->getMessage(),
        ]);

        $this->logger->error('查询电子回单失败', $logData);
    }

    /**
     * 创建或更新电子回单记录
     * @param array<string, mixed> $responseData
     */
    private function createOrUpdateReceipt(array $responseData, string $type): TransferReceipt
    {
        $transferReceipt = new TransferReceipt();
        $transferReceipt->setReceiptStatus(TransferReceiptStatus::GENERATING);
        $transferReceipt->setApplyTime(new \DateTimeImmutable());

        // 保存原始响应数据
        $rawResponse = json_encode($responseData, JSON_UNESCAPED_UNICODE);
        assert($rawResponse !== false);
        $transferReceipt->setRawResponse($rawResponse);

        // 设置申请单号（从官方API响应中获取）
        if (isset($responseData['apply_no'])) {
            $applyNo = $responseData['apply_no'];
            assert(is_string($applyNo));
            $transferReceipt->setApplyNo($applyNo);
        }

        // 尝试查找并设置关联的批次和明细
        $this->setReceiptAssociationsFromResponse($transferReceipt, $responseData);

        return $transferReceipt;
    }

    /**
     * 根据官方API响应设置回单关联关系
     * @param array<string, mixed> $responseData
     */
    private function setReceiptAssociationsFromResponse(TransferReceipt $transferReceipt, array $responseData): void
    {
        $this->setWechatIdentifiers($transferReceipt, $responseData);
        $this->associateTransferBatch($transferReceipt, $responseData);
        $this->associateTransferDetail($transferReceipt, $responseData);
    }

    /**
     * 根据官方API响应更新回单信息
     * @param array<string, mixed> $responseData
     */
    private function updateReceiptFromResponse(TransferReceipt $transferReceipt, array $responseData): void
    {
        $this->updateReceiptStatus($transferReceipt, $responseData);
        $this->updateReceiptFileInfo($transferReceipt, $responseData);
        $this->updateReceiptAssociations($transferReceipt, $responseData);
        $this->saveRawResponse($transferReceipt, $responseData);
    }

    /**
     * 更新回单状态
     * @param array<string, mixed> $responseData
     */
    private function updateReceiptStatus(TransferReceipt $transferReceipt, array $responseData): void
    {
        if (!isset($responseData['receipt_status'])) {
            return;
        }

        $receiptStatus = $responseData['receipt_status'];
        assert(is_string($receiptStatus) || is_int($receiptStatus));

        try {
            $transferReceipt->setReceiptStatus(TransferReceiptStatus::from($receiptStatus));
        } catch (\ValueError $e) {
            $this->logger->warning('未知的回单状态', [
                'receipt_status' => $receiptStatus,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 更新回单文件信息
     * @param array<string, mixed> $responseData
     */
    private function updateReceiptFileInfo(TransferReceipt $transferReceipt, array $responseData): void
    {
        // 基本信息
        if (isset($responseData['download_url'])) {
            $downloadUrl = $responseData['download_url'];
            assert(is_string($downloadUrl));
            $transferReceipt->setDownloadUrl($downloadUrl);
        }

        if (isset($responseData['hash_value'])) {
            $hashValue = $responseData['hash_value'];
            assert(is_string($hashValue));
            $transferReceipt->setHashValue($hashValue);
        }

        if (isset($responseData['file_name'])) {
            $fileName = $responseData['file_name'];
            assert(is_string($fileName));
            $transferReceipt->setFileName($fileName);
        }

        // 时间信息
        $this->updateDateTimeField($transferReceipt, $responseData, 'generate_time', 'setGenerateTime');
        $this->updateDateTimeField($transferReceipt, $responseData, 'expire_time', 'setExpireTime');

        // 大小信息
        if (isset($responseData['file_size'])) {
            $fileSize = $responseData['file_size'];
            assert(is_numeric($fileSize));
            $transferReceipt->setFileSize((int)$fileSize);
        }
    }

    /**
     * 更新日期时间字段
     * @param array<string, mixed> $responseData
     */
    private function updateDateTimeField(
        TransferReceipt $transferReceipt,
        array $responseData,
        string $fieldKey,
        string $setterMethod
    ): void {
        if (!isset($responseData[$fieldKey])) {
            return;
        }

        $timeString = $responseData[$fieldKey];
        assert(is_string($timeString));

        try {
            $dateTime = new \DateTimeImmutable($timeString);

            match ($setterMethod) {
                'setGenerateTime' => $transferReceipt->setGenerateTime($dateTime),
                'setExpireTime' => $transferReceipt->setExpireTime($dateTime),
                default => throw new \InvalidArgumentException("Unknown setter method: {$setterMethod}"),
            };
        } catch (\Exception $e) {
            $fieldDescription = match ($fieldKey) {
                'generate_time' => '生成时间',
                'expire_time' => '过期时间',
                default => $fieldKey,
            };

            $this->logger->warning("无效的{$fieldDescription}格式", [
                $fieldKey => $timeString,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 更新回单关联关系
     * @param array<string, mixed> $responseData
     */
    private function updateReceiptAssociations(TransferReceipt $transferReceipt, array $responseData): void
    {
        $this->setReceiptAssociationsFromResponse($transferReceipt, $responseData);
    }

    /**
     * 关联转账批次
     * @param array<string, mixed> $responseData
     */
    private function associateTransferBatch(TransferReceipt $transferReceipt, array $responseData): void
    {
        $transferBatch = $this->findBatchByResponse($responseData);

        if ($transferBatch !== null) {
            $transferReceipt->setTransferBatch($transferBatch);
        }
    }

    /**
     * 关联转账明细
     * @param array<string, mixed> $responseData
     */
    private function associateTransferDetail(TransferReceipt $transferReceipt, array $responseData): void
    {
        $transferDetail = $this->findTransferDetail($responseData, $transferReceipt);

        if ($transferDetail !== null) {
            $transferReceipt->setTransferDetail($transferDetail);
        }
    }

    /**
     * 设置微信官方标识符
     * @param array<string, mixed> $responseData
     */
    private function setWechatIdentifiers(TransferReceipt $transferReceipt, array $responseData): void
    {
        if (isset($responseData['batch_id'])) {
            $batchId = $responseData['batch_id'];
            assert(is_string($batchId));
            $transferReceipt->setBatchId($batchId);
        }

        if (isset($responseData['detail_id'])) {
            $detailId = $responseData['detail_id'];
            assert(is_string($detailId));
            $transferReceipt->setDetailId($detailId);
        }
    }

    /**
     * 根据响应数据查找批次
     * @param array<string, mixed> $responseData
     */
    private function findBatchByResponse(array $responseData): ?TransferBatch
    {
        if (isset($responseData['out_batch_no'])) {
            $transferBatch = $this->batchRepository->findOneBy(['outBatchNo' => $responseData['out_batch_no']]);
            if ($transferBatch !== null) {
                return $transferBatch;
            }
        }

        if (isset($responseData['batch_id'])) {
            return $this->batchRepository->findOneBy(['batchId' => $responseData['batch_id']]);
        }

        return null;
    }

    /**
     * 保存原始响应数据
     * @param array<string, mixed> $responseData
     */
    private function saveRawResponse(TransferReceipt $transferReceipt, array $responseData): void
    {
        $rawResponse = json_encode($responseData, JSON_UNESCAPED_UNICODE);
        assert($rawResponse !== false);
        $transferReceipt->setRawResponse($rawResponse);
    }

    
    
    
    /**
     * 查找转账明细（优化版本）
     * @param array<string, mixed> $responseData
     */
    private function findTransferDetail(array $responseData, TransferReceipt $transferReceipt): ?TransferDetail
    {
        // 1. 优先通过商户明细号查找
        if (isset($responseData['out_detail_no'])) {
            $transferDetail = $this->detailRepository->findOneBy(['outDetailNo' => $responseData['out_detail_no']]);
            if ($transferDetail !== null) {
                return $transferDetail;
            }
        }

        // 2. 其次通过微信明细号查找
        if (isset($responseData['detail_id'])) {
            $transferDetail = $this->detailRepository->findOneBy(['detailId' => $responseData['detail_id']]);
            if ($transferDetail !== null) {
                return $transferDetail;
            }
        }

        // 3. 最后从关联批次的第一个明细作为回退
        return $this->getFirstDetailFromBatch($transferReceipt);
    }

    /**
     * 从关联批次获取第一个明细
     */
    private function getFirstDetailFromBatch(TransferReceipt $transferReceipt): ?TransferDetail
    {
        $transferBatch = $transferReceipt->getTransferBatch();
        if ($transferBatch === null) {
            return null;
        }

        $details = $transferBatch->getDetails();
        if ($details->count() === 0) {
            return null;
        }

        $firstDetail = $details->first();
        return $firstDetail instanceof TransferDetail ? $firstDetail : null;
    }
}
