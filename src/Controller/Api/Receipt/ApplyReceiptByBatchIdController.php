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
 * 微信单号申请电子回单控制器
 *
 * 通过微信批次单号或明细单号申请电子回单
 *
 * @see https://pay.weixin.qq.com/doc/v3/merchant/4012716452
 */
#[WithMonologChannel(channel: 'wechat_pay_transfer')]
final class ApplyReceiptByBatchIdController extends AbstractController
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
    #[Route(path: '/api/wechat-pay-transfer/receipt/apply/batch-id', name: 'api_wechat_pay_receipt_apply_batch_id', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
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
}
