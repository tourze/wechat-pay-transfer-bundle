<?php

declare(strict_types=1);

namespace WechatPayTransferBundle\Controller\Api;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use WechatPayTransferBundle\Entity\TransferBatch;
use WechatPayTransferBundle\Entity\TransferDetail;
use WechatPayTransferBundle\Repository\TransferBatchRepository;
use WechatPayTransferBundle\Repository\TransferDetailRepository;
use WechatPayTransferBundle\Service\TransferApiService;

/**
 * 转账API控制器
 * 
 * 提供微信支付转账相关的RESTful API接口，包括发起转账、查询转账、撤销转账等操作。
 * 
 * @see https://pay.weixin.qq.com/doc/v3/merchant/4012711988
 */
#[WithMonologChannel(channel: 'wechat_pay_transfer')]
#[Route(path: '/api/wechat-pay-transfer', name: 'api_wechat_pay_transfer')]
final class TransferApiController extends AbstractController
{
    private TransferApiService $transferApiService;
    private TransferBatchRepository $batchRepository;
    private TransferDetailRepository $detailRepository;
    private LoggerInterface $logger;
    private ValidatorInterface $validator;

    /**
     * @param TransferBatchRepository $batchRepository
     * @param TransferDetailRepository $detailRepository
     */
    public function __construct(
        TransferApiService $transferApiService,
        TransferBatchRepository $batchRepository,
        TransferDetailRepository $detailRepository,
        LoggerInterface $logger,
        ValidatorInterface $validator
    ) {
        $this->transferApiService = $transferApiService;
        $this->batchRepository = $batchRepository;
        $this->detailRepository = $detailRepository;
        $this->logger = $logger;
        $this->validator = $validator;
    }

    /**
     * 发起转账
     * 
     * 向微信支付发起批量转账请求
     * 
     * @param Request $request HTTP请求
     * @return JsonResponse API响应
     * 
     * @see https://pay.weixin.qq.com/doc/v3/merchant/4012716452
     */
    #[Route(path: '/transfer/initiate', name: 'api_wechat_pay_transfer_initiate', methods: ['POST'])]
    public function initiateTransfer(Request $request): JsonResponse
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

    /**
     * 撤销转账
     * 
     * 撤销指定的转账批次
     * 
     * @param Request $request HTTP请求
     * @return JsonResponse API响应
     * 
     * @see https://pay.weixin.qq.com/doc/v3/merchant/4012716452
     */
    #[Route(path: '/transfer/cancel', name: 'api_wechat_pay_transfer_cancel', methods: ['POST'])]
    public function cancelTransfer(Request $request): JsonResponse
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

    /**
     * 查询转账
     * 
     * 通过商户单号或微信单号查询转账详情
     * 
     * @param Request $request HTTP请求
     * @return JsonResponse API响应
     * 
     * @see https://pay.weixin.qq.com/doc/v3/merchant/4012716452
     */
    #[Route(path: '/transfer/query', name: 'api_wechat_pay_transfer_query', methods: ['GET'])]
    public function queryTransfer(Request $request): JsonResponse
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

    /**
     * APP调起用户确认收款
     * 
     * 生成APP调起用户确认收款的参数
     * 
     * @param Request $request HTTP请求
     * @return JsonResponse API响应
     * 
     * @see https://pay.weixin.qq.com/doc/v3/merchant/4012716452
     */
    #[Route(path: '/transfer/app-confirm', name: 'api_wechat_pay_transfer_app_confirm', methods: ['POST'])]
    public function generateAppConfirmParameters(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (!is_array($data) || !isset($data['detail_id'])) {
                return new JsonResponse(['error' => '缺少转账明细ID'], Response::HTTP_BAD_REQUEST);
            }

            $transferDetail = $this->detailRepository->find($data['detail_id']);

            if ($transferDetail === null) {
                return new JsonResponse(['error' => '转账明细不存在'], Response::HTTP_NOT_FOUND);
            }

            $parameters = $this->transferApiService->generateAppConfirmParameters($transferDetail);

            return new JsonResponse([
                'success' => true,
                'message' => '生成APP确认参数成功',
                'data' => $parameters,
            ]);

        } catch (\Exception $e) {
            $this->logger->error('API生成APP确认参数失败', [
                'error' => $e->getMessage(),
                'data' => $request->getContent(),
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * JSAPI调起用户确认收款
     * 
     * 生成JSAPI调起用户确认收款的参数
     * 
     * @param Request $request HTTP请求
     * @return JsonResponse API响应
     * 
     * @see https://pay.weixin.qq.com/doc/v3/merchant/4012716452
     */
    #[Route(path: '/transfer/jsapi-confirm', name: 'api_wechat_pay_transfer_jsapi_confirm', methods: ['POST'])]
    public function generateJsApiConfirmParameters(Request $request): JsonResponse
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

    /**
     * 处理转账回调通知
     * 
     * 处理微信支付发送的转账状态变更通知
     * 
     * @param Request $request HTTP请求
     * @return JsonResponse API响应
     * 
     * @see https://pay.weixin.qq.com/doc/v3/merchant/4012716452
     */
    #[Route(path: '/transfer/notification', name: 'api_wechat_pay_transfer_notification', methods: ['POST'])]
    public function handleTransferNotification(Request $request): JsonResponse
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

    /**
     * 设置转账回调通知
     *
     * 调用微信支付官方API设置转账回调通知URL
     *
     * @param Request $request HTTP请求
     * @return JsonResponse API响应
     *
     * @see https://pay.weixin.qq.com/doc/v3/merchant/4012716452
     */
    #[Route(path: '/transfer/setup-notification', name: 'api_wechat_pay_transfer_setup_notification', methods: ['POST'])]
    public function setupTransferNotification(Request $request): JsonResponse
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

    /**
     * 可调用控制器入口
     *
     * 提供控制器的主要功能，默认返回转账API的可用操作列表
     *
     * @param Request $request HTTP请求
     * @return JsonResponse API响应
     */
    #[Route(path: '/', name: 'api_wechat_pay_transfer_index', methods: ['GET'])]
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
