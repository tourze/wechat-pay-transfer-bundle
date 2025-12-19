<?php

namespace WechatPayTransferBundle\Tests\Controller\Api\Receipt;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;
use WechatPayTransferBundle\Controller\Api\Receipt\ApplyReceiptByOutBatchNoController;

/**
 * @internal
 */
#[CoversClass(ApplyReceiptByOutBatchNoController::class)]
#[RunTestsInSeparateProcesses]
final class ApplyReceiptByOutBatchNoControllerTest extends AbstractWebTestCase
{
    protected function createTestClient(): KernelBrowser
    {
        return static::createClientWithDatabase();
    }

    public function testApplyReceiptByOutBatchNoMissingBatchNo(): void
    {
        $client = static::createClientWithDatabase();
        $this->loginAsAdmin($client);
        $payload = json_encode([
            'out_detail_no' => 'TEST_DETAIL_001'
        ], JSON_THROW_ON_ERROR);
        $client->request('POST', '/api/wechat-pay-transfer/receipt/apply/out-batch-no', [], [], [], $payload);

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertIsString($content);
        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($data);
        $this->assertSame('缺少商户批次单号', $data['error']);
    }

    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        $client = static::createClientWithDatabase();
        $this->loginAsAdmin($client);

        try {
            $client->request($method, '/api/wechat-pay-transfer/receipt/apply/out-batch-no');
        } catch (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e) {
            $this->assertSame(405, $e->getStatusCode());
            return;
        }

        $this->assertSame(405, $client->getResponse()->getStatusCode());
    }
}
