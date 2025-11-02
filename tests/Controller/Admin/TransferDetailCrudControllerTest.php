<?php

declare(strict_types=1);

namespace WechatPayTransferBundle\Tests\Controller\Admin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;
use WechatPayTransferBundle\Controller\Admin\TransferDetailCrudController;
use WechatPayTransferBundle\Entity\TransferBatch;
use WechatPayTransferBundle\Entity\TransferDetail;

/**
 * @internal
 */
#[CoversClass(TransferDetailCrudController::class)]
#[RunTestsInSeparateProcesses]
final class TransferDetailCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function getControllerService(): TransferDetailCrudController
    {
        return self::getService(TransferDetailCrudController::class);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'id_header' => ['ID'];
        yield 'batch_header' => ['批次'];
        yield 'detail_no_header' => ['明细单号'];
        yield 'amount_header' => ['转账金额'];
        yield 'remark_header' => ['转账备注'];
        yield 'openid_header' => ['用户openid'];
        yield 'username_header' => ['收款用户姓名'];
        yield 'detail_id_header' => ['微信明细单号'];
        yield 'status_header' => ['明细状态'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'batch' => ['batch'];
        yield 'outDetailNo' => ['outDetailNo'];
        yield 'transferAmount' => ['transferAmount'];
        yield 'transferRemark' => ['transferRemark'];
        yield 'openid' => ['openid'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'batch' => ['batch'];
        yield 'outDetailNo' => ['outDetailNo'];
        yield 'transferAmount' => ['transferAmount'];
        yield 'transferRemark' => ['transferRemark'];
        yield 'openid' => ['openid'];
    }

    public function testGetEntityFqcn(): void
    {
        $this->assertSame(TransferDetail::class, TransferDetailCrudController::getEntityFqcn());
    }

    public function testValidationErrors(): void
    {
        $client = $this->createAuthenticatedClient();

        $crawler = $client->request('GET', $this->generateAdminUrl('new'));
        $this->assertResponseIsSuccessful();

        $batch = self::getEntityManager()->getRepository(TransferBatch::class)->findOneBy([]);
        $this->assertNotNull($batch);

        $form = $crawler->selectButton('Create')->form();
        $formData = $form->getPhpValues();
        $this->assertIsArray($formData);
        $this->assertArrayHasKey('TransferDetail', $formData);
        $this->assertIsArray($formData['TransferDetail']);
        $formData['TransferDetail']['batch'] = (string) $batch->getId();
        $formData['TransferDetail']['outDetailNo'] = '';
        $formData['TransferDetail']['transferAmount'] = -1;
        $formData['TransferDetail']['transferRemark'] = '';
        $formData['TransferDetail']['openid'] = '';

        $crawler = $client->submit($form, $formData);
        $this->assertResponseStatusCodeSame(422);

        $errorText = $crawler->filter('.invalid-feedback')->text();
        $this->assertStringContainsString('should not be blank', $errorText);
    }

    protected function onSetUp(): void
    {
        $client = self::createClientWithDatabase();
        self::getClient($client);
    }

    public function testFixtureLoaded(): void
    {
        self::createClientWithDatabase();
        $repository = self::getEntityManager()->getRepository(TransferDetail::class);
        $this->assertGreaterThan(0, $repository->count([]));
    }
}
