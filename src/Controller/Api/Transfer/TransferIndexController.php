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

/**
 * 转账API索引控制器
 *
 * 提供转账API的可用操作列表
 */
#[WithMonologChannel(channel: 'wechat_pay_transfer')]
final class TransferIndexController extends AbstractController
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param Request $request HTTP请求
     * @return JsonResponse API响应
     */
    #[Route(path: '/api/wechat-pay-transfer/', name: 'api_wechat_pay_transfer_index', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        try {
            return new JsonResponse([
                'success' => true,
                'message' => '微信支付转账API',
                'data' => [
                    'available_endpoints' => [
                        'POST /transfer/initiate' => '发起转账',
                        'POST /transfer/cancel' => '撤销转账',
                        'GET /transfer/query' => '查询转账',
                        'POST /transfer/app-confirm' => 'APP确认收款',
                        'POST /transfer/jsapi-confirm' => 'JSAPI确认收款',
                        'POST /transfer/notification' => '处理回调通知',
                        'POST /transfer/setup-notification' => '设置回调通知',
                    ],
                    'version' => '1.0.0',
                ],
            ]);

        } catch (\Exception $e) {
            $this->logger->error('API索引请求失败', [
                'error' => $e->getMessage(),
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
