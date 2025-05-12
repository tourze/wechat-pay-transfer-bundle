<?php

namespace WechatPayTransferBundle\Tests\Enum;

use PHPUnit\Framework\TestCase;
use WechatPayTransferBundle\Enum\TransferBatchStatus;

class TransferBatchStatusTest extends TestCase
{
    public function testValues_existAndMatch(): void
    {
        $this->assertSame('PROCESSING', TransferBatchStatus::PROCESSING->value);
        $this->assertSame('FINISHED', TransferBatchStatus::FINISHED->value);
        $this->assertSame('CLOSED', TransferBatchStatus::CLOSED->value);
    }

    public function testGetLabel_returnsCorrectLabel(): void
    {
        $this->assertSame('转账中', TransferBatchStatus::PROCESSING->getLabel());
        $this->assertSame('已完成', TransferBatchStatus::FINISHED->getLabel());
        $this->assertSame('已关闭', TransferBatchStatus::CLOSED->getLabel());
    }
} 