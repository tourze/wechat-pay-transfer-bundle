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
 * 设置转账回调通知控制器
 *
 * 调用微信支付官方API设置转账回调通知URL
 *
 * @see https://pay.weixin.qq.com/doc/v3/merchant/4012716452
 */
#[WithMonologChannel(channel: 'wechat_pay_transfer')]
final class SetupTransferNotificationController extends AbstractController
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
    #[Route(path: '/api/wechat-pay-transfer/transfer/setup-notification', name: 'api_wechat_pay_transfer_setup_notification', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (!is_array($data) || !isset($data['notify_url'])) {
                return new JsonResponse(['error' => '缺少回调通知URL'], Response::HTTP_BAD_REQUEST);
            }

            assert(is_string($data['notify_url']));
            $notifyUrl = $data['notify_url'];
            $mchid = $data['mchid'] ?? null;
            if ($mchid !== null) {
                assert(is_string($mchid));
            }

            $result = $this->transferApiService->setupTransferNotification($notifyUrl, $mchid);

            return new JsonResponse([
                'success' => true,
                'message' => '设置转账回调通知成功',
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            $this->logger->error('API设置转账回调通知失败', [
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
