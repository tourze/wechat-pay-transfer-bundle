<?php

declare(strict_types=1);

namespace WechatPayTransferBundle\Controller\Api;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use WechatPayTransferBundle\Entity\TransferBatch;
use WechatPayTransferBundle\Entity\TransferReceipt;
use WechatPayTransferBundle\Repository\TransferBatchRepository;
use WechatPayTransferBundle\Service\TransferReceiptApiService;

/**
 * 电子回单API控制器
 * 
 * 提供微信支付转账电子回单相关的RESTful API接口，包括申请回单、查询回单、下载回单等操作。
 * 
 * @see https://pay.weixin.qq.com/doc/v3/merchant/4012711988
 */
#[WithMonologChannel(channel: 'wechat_pay_transfer')]
#[Route(path: '/api/wechat-pay-transfer/receipt', name: 'api_wechat_pay_transfer_receipt')]
final class TransferReceiptApiController extends AbstractController
{
    private TransferReceiptApiService $receiptApiService;

    /**
     */
    private TransferBatchRepository $batchRepository;

    private LoggerInterface $logger;

    /**
     * @param TransferBatchRepository $batchRepository
     */
    public function __construct(
        TransferReceiptApiService $receiptApiService,
        TransferBatchRepository $batchRepository,
        LoggerInterface $logger
    ) {
        $this->receiptApiService = $receiptApiService;
        $this->batchRepository = $batchRepository;
        $this->logger = $logger;
    }

    /**
     * 商户单号申请电子回单
     * 
     * 通过商户批次单号或明细单号申请电子回单
     * 
     * @param Request $request HTTP请求
     * @return JsonResponse API响应
     * 
     * @see https://pay.weixin.qq.com/doc/v3/merchant/4012716452
     */
    #[Route(path: '/apply/out-batch-no', name: 'api_wechat_pay_receipt_apply_out_batch_no', methods: ['POST'])]
    public function applyReceiptByOutBatchNo(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (!is_array($data) || !isset($data['out_batch_no'])) {
                return new JsonResponse(['error' => '缺少商户批次单号'], Response::HTTP_BAD_REQUEST);
            }

            assert(is_string($data['out_batch_no']));
            $outBatchNo = $data['out_batch_no'];
            $outDetailNo = $data['out_detail_no'] ?? null;
            if ($outDetailNo !== null) {
                assert(is_string($outDetailNo));
            }

            $receipt = $this->receiptApiService->applyReceiptByOutBatchNo($outBatchNo, $outDetailNo);

            return new JsonResponse([
                'success' => true,
                'message' => '申请电子回单成功',
                'data' => [
                    'receipt_id' => $receipt->getId(),
                    'apply_no' => $receipt->getApplyNo(),
                    'receipt_status' => $receipt->getReceiptStatus()?->value,
                    'apply_time' => $receipt->getApplyTime()?->format('Y-m-d H:i:s'),
                ],
            ]);

        } catch (\Exception $e) {
            $this->logger->error('API申请电子回单失败', [
                'error' => $e->getMessage(),
                'data' => $request->getContent(),
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 微信单号申请电子回单
     * 
     * 通过微信批次单号或明细单号申请电子回单
     * 
     * @param Request $request HTTP请求
     * @return JsonResponse API响应
     * 
     * @see https://pay.weixin.qq.com/doc/v3/merchant/4012716452
     */
    #[Route(path: '/apply/batch-id', name: 'api_wechat_pay_receipt_apply_batch_id', methods: ['POST'])]
    public function applyReceiptByBatchId(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (!is_array($data) || !isset($data['batch_id'])) {
                return new JsonResponse(['error' => '缺少微信批次单号'], Response::HTTP_BAD_REQUEST);
            }

            assert(is_string($data['batch_id']));
            $batchId = $data['batch_id'];
            $detailId = $data['detail_id'] ?? null;
            if ($detailId !== null) {
                assert(is_string($detailId));
            }

            $receipt = $this->receiptApiService->applyReceiptByBatchId($batchId, $detailId);

            return new JsonResponse([
                'success' => true,
                'message' => '申请电子回单成功',
                'data' => [
                    'receipt_id' => $receipt->getId(),
                    'apply_no' => $receipt->getApplyNo(),
                    'receipt_status' => $receipt->getReceiptStatus()?->value,
                    'apply_time' => $receipt->getApplyTime()?->format('Y-m-d H:i:s'),
                ],
            ]);

        } catch (\Exception $e) {
            $this->logger->error('API申请电子回单失败', [
                'error' => $e->getMessage(),
                'data' => $request->getContent(),
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 商户单号查询电子回单
     * 
     * 通过商户批次单号或明细单号查询电子回单状态
     * 
     * @param Request $request HTTP请求
     * @return JsonResponse API响应
     * 
     * @see https://pay.weixin.qq.com/doc/v3/merchant/4012716452
     */
    #[Route(path: '/query/out-batch-no', name: 'api_wechat_pay_receipt_query_out_batch_no', methods: ['GET'])]
    public function queryReceiptByOutBatchNo(Request $request): JsonResponse
    {
        try {
            $outBatchNo = $request->query->get('out_batch_no');
            $outDetailNo = $request->query->get('out_detail_no');

            if ($outBatchNo === null) {
                return new JsonResponse(['error' => '缺少商户批次单号'], Response::HTTP_BAD_REQUEST);
            }

            assert(is_string($outBatchNo));
            if ($outDetailNo !== null) {
                assert(is_string($outDetailNo));
            }

            $receipt = $this->receiptApiService->queryReceiptByOutBatchNo($outBatchNo, $outDetailNo);

            if ($receipt === null) {
                return new JsonResponse(['error' => '未找到电子回单'], Response::HTTP_NOT_FOUND);
            }

            return new JsonResponse([
                'success' => true,
                'message' => '查询电子回单成功',
                'data' => [
                    'receipt_id' => $receipt->getId(),
                    'apply_no' => $receipt->getApplyNo(),
                    'receipt_status' => $receipt->getReceiptStatus()?->value,
                    'download_url' => $receipt->getDownloadUrl(),
                    'file_name' => $receipt->getFileName(),
                    'file_size' => $receipt->getFileSize(),
                    'generate_time' => $receipt->getGenerateTime(),
                    'expire_time' => $receipt->getExpireTime(),
                    'hash_value' => $receipt->getHashValue(),
                ],
            ]);

        } catch (\Exception $e) {
            $this->logger->error('API查询电子回单失败', [
                'error' => $e->getMessage(),
                'query' => $request->query->all(),
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 微信单号查询电子回单
     * 
     * 通过微信批次单号或明细单号查询电子回单状态
     * 
     * @param Request $request HTTP请求
     * @return JsonResponse API响应
     * 
     * @see https://pay.weixin.qq.com/doc/v3/merchant/4012716452
     */
    #[Route(path: '/query/batch-id', name: 'api_wechat_pay_receipt_query_batch_id', methods: ['GET'])]
    public function queryReceiptByBatchId(Request $request): JsonResponse
    {
        try {
            $batchId = $request->query->get('batch_id');
            $detailId = $request->query->get('detail_id');

            if ($batchId === null) {
                return new JsonResponse(['error' => '缺少微信批次单号'], Response::HTTP_BAD_REQUEST);
            }

            assert(is_string($batchId));
            if ($detailId !== null) {
                assert(is_string($detailId));
            }

            $receipt = $this->receiptApiService->queryReceiptByBatchId($batchId, $detailId);

            if ($receipt === null) {
                return new JsonResponse(['error' => '未找到电子回单'], Response::HTTP_NOT_FOUND);
            }

            return new JsonResponse([
                'success' => true,
                'message' => '查询电子回单成功',
                'data' => [
                    'receipt_id' => $receipt->getId(),
                    'apply_no' => $receipt->getApplyNo(),
                    'receipt_status' => $receipt->getReceiptStatus()?->value,
                    'download_url' => $receipt->getDownloadUrl(),
                    'file_name' => $receipt->getFileName(),
                    'file_size' => $receipt->getFileSize(),
                    'generate_time' => $receipt->getGenerateTime(),
                    'expire_time' => $receipt->getExpireTime(),
                    'hash_value' => $receipt->getHashValue(),
                ],
            ]);

        } catch (\Exception $e) {
            $this->logger->error('API查询电子回单失败', [
                'error' => $e->getMessage(),
                'query' => $request->query->all(),
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 下载电子回单
     * 
     * 通过下载URL下载电子回单文件
     * 
     * @param Request $request HTTP请求
     * @return Response 文件下载响应
     * 
     * @see https://pay.weixin.qq.com/doc/v3/merchant/4012716452
     */
    #[Route(path: '/download', name: 'api_wechat_pay_receipt_download', methods: ['GET', 'POST'])]
    public function downloadReceipt(Request $request): Response
    {
        try {
            $downloadUrl = $request->query->get('download_url') ?? $request->request->get('download_url');

            if ($downloadUrl === null) {
                return new JsonResponse(['error' => '缺少下载URL'], Response::HTTP_BAD_REQUEST);
            }

            assert(is_string($downloadUrl));
            $fileContent = $this->receiptApiService->downloadReceipt($downloadUrl);

            // 设置响应头
            $response = new Response($fileContent);
            $disposition = $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                'transfer_receipt.pdf'
            );
            $response->headers->set('Content-Disposition', $disposition);
            $response->headers->set('Content-Type', 'application/pdf');

            $this->logger->info('下载电子回单成功', [
                'download_url' => $downloadUrl,
                'file_size' => strlen($fileContent),
            ]);

            return $response;

        } catch (\Exception $e) {
            $this->logger->error('API下载电子回单失败', [
                'error' => $e->getMessage(),
                'request' => $request->request->all(),
                'query' => $request->query->all(),
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 批量申请电子回单
     * 
     * 为指定的转账批次批量申请电子回单
     * 
     * @param Request $request HTTP请求
     * @return JsonResponse API响应
     */
    #[Route(path: '/batch-apply', name: 'api_wechat_pay_receipt_batch_apply', methods: ['POST'])]
    public function batchApplyReceipts(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (!is_array($data) || !isset($data['batch_id'])) {
                return new JsonResponse(['error' => '缺少转账批次ID'], Response::HTTP_BAD_REQUEST);
            }

            $transferBatch = $this->batchRepository->find($data['batch_id']);

            if ($transferBatch === null) {
                return new JsonResponse(['error' => '转账批次不存在'], Response::HTTP_NOT_FOUND);
            }

            $results = $this->receiptApiService->batchApplyReceipts($transferBatch);
            assert(isset($results['batch']) && $results['batch'] instanceof TransferReceipt);
            assert(is_array($results['details']));

            return new JsonResponse([
                'success' => true,
                'message' => '批量申请电子回单成功',
                'data' => [
                    'batch_receipt_id' => $results['batch']->getId(),
                    'detail_count' => count($results['details']),
                    'detail_receipt_ids' => array_map(function (TransferReceipt $receipt) {
                        return $receipt->getId();
                    }, $results['details']),
                ],
            ]);

        } catch (\Exception $e) {
            $this->logger->error('API批量申请电子回单失败', [
                'error' => $e->getMessage(),
                'data' => $request->getContent(),
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
