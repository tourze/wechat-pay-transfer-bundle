<?php

declare(strict_types=1);

namespace WechatPayTransferBundle\Controller\Api\Receipt;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use WechatPayTransferBundle\Service\TransferReceiptApiService;

/**
 * 下载电子回单控制器
 *
 * 通过下载URL下载电子回单文件
 *
 * @see https://pay.weixin.qq.com/doc/v3/merchant/4012716452
 */
#[WithMonologChannel(channel: 'wechat_pay_transfer')]
final class DownloadReceiptController extends AbstractController
{
    public function __construct(
        private readonly TransferReceiptApiService $receiptApiService,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param Request $request HTTP请求
     * @return Response 文件下载响应
     */
    #[Route(path: '/api/wechat-pay-transfer/receipt/download', name: 'api_wechat_pay_receipt_download', methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        try {
            $downloadUrl = $request->query->get('download_url') ?? $request->request->get('download_url');

            if ($downloadUrl === null) {
                return new JsonResponse(['error' => '缺少下载URL'], Response::HTTP_BAD_REQUEST);
            }

            assert(is_string($downloadUrl));
            $fileContent = $this->receiptApiService->downloadReceipt($downloadUrl);

            // 设置响应头
            $response = new Response($fileContent);
            $disposition = $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                'transfer_receipt.pdf'
            );
            $response->headers->set('Content-Disposition', $disposition);
            $response->headers->set('Content-Type', 'application/pdf');

            $this->logger->info('下载电子回单成功', [
                'download_url' => $downloadUrl,
                'file_size' => strlen($fileContent),
            ]);

            return $response;

        } catch (\Exception $e) {
            $this->logger->error('API下载电子回单失败', [
                'error' => $e->getMessage(),
                'request' => $request->request->all(),
                'query' => $request->query->all(),
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
