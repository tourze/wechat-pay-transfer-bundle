<?php

namespace WechatPayTransferBundle\Tests\Controller\Api\Receipt;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;
use WechatPayTransferBundle\Controller\Api\Receipt\QueryReceiptByBatchIdController;

/**
 * @internal
 */
#[CoversClass(QueryReceiptByBatchIdController::class)]
#[RunTestsInSeparateProcesses]
final class QueryReceiptByBatchIdControllerTest extends AbstractWebTestCase
{
    protected function createTestClient(): KernelBrowser
    {
        return static::createClientWithDatabase();
    }

    public function testQueryReceiptByBatchIdMissingBatchId(): void
    {
        $client = static::createClientWithDatabase();
        $this->loginAsAdmin($client);
        $client->request('GET', '/api/wechat-pay-transfer/receipt/query/batch-id');

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertIsString($content);
        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($data);
        $this->assertSame('缺少微信批次单号', $data['error']);
    }

    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        $client = static::createClientWithDatabase();
        $this->loginAsAdmin($client);

        try {
            $client->request($method, '/api/wechat-pay-transfer/receipt/query/batch-id');
        } catch (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e) {
            $this->assertSame(405, $e->getStatusCode());
            return;
        }

        $this->assertSame(405, $client->getResponse()->getStatusCode());
    }
}
