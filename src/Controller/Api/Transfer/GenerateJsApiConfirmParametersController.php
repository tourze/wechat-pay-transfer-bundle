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
use WechatPayTransferBundle\Repository\TransferDetailRepository;
use WechatPayTransferBundle\Service\TransferApiService;

/**
 * JSAPI调起用户确认收款控制器
 *
 * 生成JSAPI调起用户确认收款的参数
 *
 * @see https://pay.weixin.qq.com/doc/v3/merchant/4012716452
 */
#[WithMonologChannel(channel: 'wechat_pay_transfer')]
final class GenerateJsApiConfirmParametersController extends AbstractController
{
    public function __construct(
        private readonly TransferApiService $transferApiService,
        private readonly TransferDetailRepository $detailRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param Request $request HTTP请求
     * @return JsonResponse API响应
     */
    #[Route(path: '/api/wechat-pay-transfer/transfer/jsapi-confirm', name: 'api_wechat_pay_transfer_jsapi_confirm', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (!is_array($data) || !isset($data['detail_id']) || !isset($data['openid'])) {
                return new JsonResponse(['error' => '缺少转账明细ID或用户openid'], Response::HTTP_BAD_REQUEST);
            }

            $transferDetail = $this->detailRepository->find($data['detail_id']);

            if ($transferDetail === null) {
                return new JsonResponse(['error' => '转账明细不存在'], Response::HTTP_NOT_FOUND);
            }

            assert(is_string($data['openid']));
            $parameters = $this->transferApiService->generateJsApiConfirmParameters($transferDetail, $data['openid']);

            return new JsonResponse([
                'success' => true,
                'message' => '生成JSAPI确认参数成功',
                'data' => $parameters,
            ]);

        } catch (\Exception $e) {
            $this->logger->error('API生成JSAPI确认参数失败', [
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
