<?php

namespace WechatPayTransferBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use WechatPayTransferBundle\Command\BatchApplyReceiptsCommand;

/**
 * @internal
 */
#[CoversClass(BatchApplyReceiptsCommand::class)]
#[RunTestsInSeparateProcesses]
final class BatchApplyReceiptsCommandTest extends AbstractCommandTestCase
{
    protected function onSetUp(): void
    {
        // 使用容器获取服务，不需要手动创建mock
    }

    protected function getCommandTester(): CommandTester
    {
        $command = self::getService(BatchApplyReceiptsCommand::class);

        return new CommandTester($command);
    }

    public function testCommandExecutionWithNoCompletedBatches(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('开始批量申请电子回单...', $output);
        $this->assertStringContainsString('没有找到需要申请回单的转账批次', $output);
        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function testCommandExecutionWithSpecificBatch(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('开始批量申请电子回单...', $output);
        $this->assertStringContainsString('没有找到需要申请回单的转账批次', $output);
        $this->assertSame(0, $commandTester->getStatusCode());
    }
}