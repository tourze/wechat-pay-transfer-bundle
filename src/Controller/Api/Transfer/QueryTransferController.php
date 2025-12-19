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
 * 查询转账控制器
 *
 * 通过商户单号或微信单号查询转账详情
 *
 * @see https://pay.weixin.qq.com/doc/v3/merchant/4012716452
 */
#[WithMonologChannel(channel: 'wechat_pay_transfer')]
final class QueryTransferController extends AbstractController
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
    #[Route(path: '/api/wechat-pay-transfer/transfer/query', name: 'api_wechat_pay_transfer_query', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $outBatchNo = $request->query->get('out_batch_no');
            $batchId = $request->query->get('batch_id');
            $needQueryDetail = $request->query->getBoolean('need_query_detail', false);

            if (($outBatchNo === null || $outBatchNo === '') && ($batchId === null || $batchId === '')) {
                return new JsonResponse(['error' => '必须提供商户批次单号或微信批次单号'], Response::HTTP_BAD_REQUEST);
            }

            if ($outBatchNo !== null && $outBatchNo !== '') {
                assert(is_string($outBatchNo));
                $result = $this->transferApiService->queryTransferByOutBatchNo($outBatchNo, $needQueryDetail);
            } else {
                assert(is_string($batchId));
                $result = $this->transferApiService->queryTransferByBatchId($batchId, $needQueryDetail);
            }

            return new JsonResponse([
                'success' => true,
                'message' => '查询成功',
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            $this->logger->error('API查询转账失败', [
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
