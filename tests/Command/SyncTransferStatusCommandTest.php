<?php

namespace WechatPayTransferBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use WechatPayTransferBundle\Command\SyncTransferStatusCommand;

/**
 * @internal
 */
#[CoversClass(SyncTransferStatusCommand::class)]
#[RunTestsInSeparateProcesses]
final class SyncTransferStatusCommandTest extends AbstractCommandTestCase
{
    protected function onSetUp(): void
    {
        // 使用容器获取服务，不需要手动创建mock
    }

    protected function getCommandTester(): CommandTester
    {
        $command = self::getService(SyncTransferStatusCommand::class);

        return new CommandTester($command);
    }

    public function testCommandExecutionWithNoBatchesAndDetails(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('开始同步转账状态...', $output);
        $this->assertStringContainsString('状态同步完成!', $output);
        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function testOptionBatchLimit(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute(['--batch-limit' => 25]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('状态同步完成!', $output);
        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function testOptionDetailLimit(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute(['--detail-limit' => 75]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('状态同步完成!', $output);
        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function testOptionStatus(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute(['--status' => 'PROCESSING']);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('状态同步完成!', $output);
        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function testOptionForceUpdate(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute(['--force-update' => true]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('状态同步完成!', $output);
        $this->assertSame(0, $commandTester->getStatusCode());
    }
}