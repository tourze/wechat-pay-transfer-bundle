<?php

namespace WechatPayTransferBundle\Tests\Enum;

use PHPUnit\Framework\TestCase;
use WechatPayTransferBundle\Enum\TransferDetailStatus;

class TransferDetailStatusTest extends TestCase
{
    public function testValues_existAndMatch(): void
    {
        $this->assertSame('INIT', TransferDetailStatus::INIT->value);
        $this->assertSame('WAIT_PAY', TransferDetailStatus::WAIT_PAY->value);
        $this->assertSame('PROCESSING', TransferDetailStatus::PROCESSING->value);
        $this->assertSame('SUCCESS', TransferDetailStatus::SUCCESS->value);
        $this->assertSame('FAIL', TransferDetailStatus::FAIL->value);
    }

    public function testGetLabel_returnsCorrectLabel(): void
    {
        $this->assertSame('初始态', TransferDetailStatus::INIT->getLabel());
        $this->assertSame('待确认', TransferDetailStatus::WAIT_PAY->getLabel());
        $this->assertSame('转账中', TransferDetailStatus::PROCESSING->getLabel());
        $this->assertSame('转账成功', TransferDetailStatus::SUCCESS->getLabel());
        $this->assertSame('转账失败', TransferDetailStatus::FAIL->getLabel());
    }
} 