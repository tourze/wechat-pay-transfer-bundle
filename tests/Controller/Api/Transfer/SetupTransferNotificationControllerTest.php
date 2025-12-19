<?php

namespace WechatPayTransferBundle\Tests\Controller\Api\Transfer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;
use WechatPayTransferBundle\Controller\Api\Transfer\SetupTransferNotificationController;

/**
 * @internal
 */
#[CoversClass(SetupTransferNotificationController::class)]
#[RunTestsInSeparateProcesses]
final class SetupTransferNotificationControllerTest extends AbstractWebTestCase
{
    protected function createTestClient(): KernelBrowser
    {
        return static::createClientWithDatabase();
    }

    public function testMissingNotifyUrl(): void
    {
        $client = static::createClientWithDatabase();
        $this->loginAsAdmin($client);
        $payload = json_encode([], JSON_THROW_ON_ERROR);
        $client->request('POST', '/api/wechat-pay-transfer/transfer/setup-notification', [], [], [], $payload);

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertIsString($content);
        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($data);
        $this->assertSame('缺少回调通知URL', $data['error']);
    }

    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        $client = static::createClientWithDatabase();
        $this->loginAsAdmin($client);

        try {
            $client->request($method, '/api/wechat-pay-transfer/transfer/setup-notification');
        } catch (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e) {
            $this->assertSame(405, $e->getStatusCode());
            return;
        }

        $this->assertSame(405, $client->getResponse()->getStatusCode());
    }
}
