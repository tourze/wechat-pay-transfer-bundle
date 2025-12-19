<?php

declare(strict_types=1);

namespace WechatPayTransferBundle\Controller\Api\Receipt;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use WechatPayTransferBundle\Service\TransferReceiptApiService;

/**
 * 商户单号查询电子回单控制器
 *
 * 通过商户批次单号或明细单号查询电子回单状态
 *
 * @see https://pay.weixin.qq.com/doc/v3/merchant/4012716452
 */
#[WithMonologChannel(channel: 'wechat_pay_transfer')]
final class QueryReceiptByOutBatchNoController extends AbstractController
{
    public function __construct(
        private readonly TransferReceiptApiService $receiptApiService,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param Request $request HTTP请求
     * @return JsonResponse API响应
     */
    #[Route(path: '/api/wechat-pay-transfer/receipt/query/out-batch-no', name: 'api_wechat_pay_receipt_query_out_batch_no', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
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
}
