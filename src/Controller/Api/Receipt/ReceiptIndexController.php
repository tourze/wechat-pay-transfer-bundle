<?php

declare(strict_types=1);

namespace WechatPayTransferBundle\Controller\Api\Receipt;

use Monolog\Attribute\WithMonologChannel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * 电子回单API文档控制器
 *
 * 返回微信支付转账电子回单API的可用端点列表
 */
#[WithMonologChannel(channel: 'wechat_pay_transfer')]
final class ReceiptIndexController extends AbstractController
{
    /**
     * @param Request $request HTTP请求
     * @return JsonResponse API响应
     */
    #[Route(path: '/api/wechat-pay-transfer/receipt', name: 'api_wechat_pay_transfer_receipt_docs', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'message' => '微信支付转账电子回单API',
            'data' => [
                'available_endpoints' => [
                    'POST /receipt/apply/out-batch-no' => '按商户批次单号申请电子回单',
                    'POST /receipt/apply/batch-id' => '按微信批次单号申请电子回单',
                    'GET /receipt/query/out-batch-no' => '按商户批次单号查询电子回单',
                    'GET /receipt/query/batch-id' => '按微信批次单号查询电子回单',
                    'GET /receipt/download' => '下载电子回单',
                    'POST /receipt/batch-apply' => '批量申请电子回单',
                ],
                'version' => '1.0.0',
            ],
        ]);
    }
}
