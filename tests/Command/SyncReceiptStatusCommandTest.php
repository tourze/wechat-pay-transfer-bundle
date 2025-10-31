<?php

namespace WechatPayTransferBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use WechatPayTransferBundle\Command\SyncReceiptStatusCommand;

/**
 * @internal
 */
#[CoversClass(SyncReceiptStatusCommand::class)]
#[RunTestsInSeparateProcesses]
final class SyncReceiptStatusCommandTest extends AbstractCommandTestCase
{
    protected function onSetUp(): void
    {
        // 使用容器获取服务，不需要手动创建mock
    }

    protected function getCommandTester(): CommandTester
    {
        $command = self::getService(SyncReceiptStatusCommand::class);

        return new CommandTester($command);
    }

    public function testCommandExecutionWithNoReceipts(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('开始同步电子回单状态...', $output);
        $this->assertStringContainsString('电子回单状态同步完成!', $output);
        $this->assertStringContainsString('同步数量: 0', $output);
        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function testOptionLimit(): void
    {
        $commandTester = $this->getCommandTester();

        // 测试默认limit值
        $commandTester->execute([]);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('同步数量: 0', $output);

        // 测试自定义limit值
        $commandTester->execute(['--limit' => '100']);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('同步数量: 0', $output);
    }

    public function testOptionStatus(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute(['--status' => 'GENERATING']);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('同步数量: 0', $output);
        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function testOptionBatchId(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute(['--batch-id' => 'TEST_BATCH_001']);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('同步数量: 0', $output);
        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function testOptionOutBatchNo(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute(['--out-batch-no' => 'MERCHANT_BATCH_001']);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('商户批次号: MERCHANT_BATCH_001', $output);
        $this->assertStringContainsString('同步数量: 0', $output);
        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function testOptionForceUpdate(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute(['--force-update' => true]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('同步数量: 0', $output);
        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function testOptionAutoDownload(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute(['--auto-download' => true]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('同步数量: 0', $output);
        $this->assertSame(0, $commandTester->getStatusCode());
    }
}