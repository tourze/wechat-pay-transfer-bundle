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
use WechatPayTransferBundle\Entity\TransferReceipt;
use WechatPayTransferBundle\Repository\TransferBatchRepository;
use WechatPayTransferBundle\Service\TransferReceiptApiService;

/**
 * 批量申请电子回单控制器
 *
 * 为指定的转账批次批量申请电子回单
 */
#[WithMonologChannel(channel: 'wechat_pay_transfer')]
final class BatchApplyReceiptsController extends AbstractController
{
    public function __construct(
        private readonly TransferReceiptApiService $receiptApiService,
        private readonly TransferBatchRepository $batchRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param Request $request HTTP请求
     * @return JsonResponse API响应
     */
    #[Route(path: '/api/wechat-pay-transfer/receipt/batch-apply', name: 'api_wechat_pay_receipt_batch_apply', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
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
