<?php

declare(strict_types=1);

namespace WechatPayTransferBundle\Controller\Api\Transfer;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use WechatPayTransferBundle\Service\TransferApiService;

/**
 * 撤销转账控制器
 *
 * 撤销指定的转账批次
 *
 * @see https://pay.weixin.qq.com/doc/v3/merchant/4012716452
 */
#[WithMonologChannel(channel: 'wechat_pay_transfer')]
final class CancelTransferController extends AbstractController
{
    public function __construct(
        private readonly TransferApiService $transferApiService,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param Request $request HTTP请求
     * @return JsonResponse API响应
     */
    #[Route(path: '/api/wechat-pay-transfer/transfer/cancel', name: 'api_wechat_pay_transfer_cancel', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (!is_array($data) || !isset($data['out_batch_no'])) {
                return new JsonResponse(['error' => '缺少商户批次单号'], Response::HTTP_BAD_REQUEST);
            }

            assert(is_string($data['out_batch_no']));
            $result = $this->transferApiService->cancelTransfer($data['out_batch_no']);

            return new JsonResponse([
                'success' => true,
                'message' => '转账撤销成功',
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            $this->logger->error('API撤销转账失败', [
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
