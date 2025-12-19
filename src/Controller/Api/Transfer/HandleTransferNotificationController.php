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
 * 处理转账回调通知控制器
 *
 * 处理微信支付发送的转账状态变更通知
 *
 * @see https://pay.weixin.qq.com/doc/v3/merchant/4012716452
 */
#[WithMonologChannel(channel: 'wechat_pay_transfer')]
final class HandleTransferNotificationController extends AbstractController
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
    #[Route(path: '/api/wechat-pay-transfer/transfer/notification', name: 'api_wechat_pay_transfer_notification', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (!is_array($data)) {
                return new JsonResponse(['error' => '无效的JSON数据'], Response::HTTP_BAD_REQUEST);
            }

            /** @var array<string, mixed> $data */
            $success = $this->transferApiService->handleTransferNotification($data);

            return new JsonResponse([
                'success' => $success,
                'message' => $success ? '回调处理成功' : '回调处理失败',
            ]);

        } catch (\Exception $e) {
            $this->logger->error('API处理转账回调失败', [
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
