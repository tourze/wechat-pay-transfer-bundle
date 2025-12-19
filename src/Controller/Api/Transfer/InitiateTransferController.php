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
use Symfony\Component\Validator\Validator\ValidatorInterface;
use WechatPayTransferBundle\Repository\TransferBatchRepository;
use WechatPayTransferBundle\Service\TransferApiService;

/**
 * 发起转账控制器
 *
 * 向微信支付发起批量转账请求
 *
 * @see https://pay.weixin.qq.com/doc/v3/merchant/4012716452
 */
#[WithMonologChannel(channel: 'wechat_pay_transfer')]
final class InitiateTransferController extends AbstractController
{
    public function __construct(
        private readonly TransferApiService $transferApiService,
        private readonly TransferBatchRepository $batchRepository,
        private readonly LoggerInterface $logger,
        private readonly ValidatorInterface $validator
    ) {
    }

    /**
     * @param Request $request HTTP请求
     * @return JsonResponse API响应
     */
    #[Route(path: '/api/wechat-pay-transfer/transfer/initiate', name: 'api_wechat_pay_transfer_initiate', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (!is_array($data)) {
                return new JsonResponse(['error' => '无效的JSON数据'], Response::HTTP_BAD_REQUEST);
            }

            // 查找转账批次
            $batchId = $data['batch_id'] ?? null;
            $transferBatch = $this->batchRepository->find($batchId);

            if ($transferBatch === null) {
                return new JsonResponse(['error' => '转账批次不存在'], Response::HTTP_NOT_FOUND);
            }

            // 验证数据
            $errors = $this->validator->validate($transferBatch);
            if (count($errors) > 0) {
                return new JsonResponse(['error' => '数据验证失败', 'errors' => (string)$errors], Response::HTTP_BAD_REQUEST);
            }

            // 发起转账
            $result = $this->transferApiService->initiateTransfer($transferBatch);

            return new JsonResponse([
                'success' => true,
                'message' => '转账发起成功',
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            $this->logger->error('API发起转账失败', [
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
