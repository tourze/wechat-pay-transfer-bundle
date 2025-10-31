<?php

namespace WechatPayTransferBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use WechatPayTransferBundle\Command\CleanupExpiredDataCommand;

/**
 * @internal
 */
#[CoversClass(CleanupExpiredDataCommand::class)]
#[RunTestsInSeparateProcesses]
final class CleanupExpiredDataCommandTest extends AbstractCommandTestCase
{
    protected function onSetUp(): void
    {
        // 使用容器获取服务，不需要手动创建mock
    }

    protected function getCommandTester(): CommandTester
    {
        $command = self::getService(CleanupExpiredDataCommand::class);

        return new CommandTester($command);
    }

    public function testCommandExecutionWithNoExpiredData(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('开始清理过期数据...', $output);
        $this->assertStringContainsString('没有发现需要清理的过期数据', $output);
        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function testCommandExecutionWithDryRun(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute(['--dry-run' => true]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('没有发现需要清理的过期数据', $output);
        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function testCommandExecutionWithForceOption(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute(['--force' => true]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('没有发现需要清理的过期数据', $output);
        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function testOptionDays(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute(['--days' => 60]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('没有发现需要清理的过期数据', $output);
        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function testOptionReceiptDays(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute(['--receipt-days' => 90]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('没有发现需要清理的过期数据', $output);
        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function testOptionDryRun(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute(['--dry-run' => true]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('没有发现需要清理的过期数据', $output);
        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function testOptionForce(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute(['--force' => true]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('没有发现需要清理的过期数据', $output);
        $this->assertSame(0, $commandTester->getStatusCode());
    }
}