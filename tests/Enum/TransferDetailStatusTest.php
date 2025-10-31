<?php

namespace WechatPayTransferBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use WechatPayTransferBundle\Enum\TransferDetailStatus;

/**
 * @internal
 */
#[CoversClass(TransferDetailStatus::class)]
final class TransferDetailStatusTest extends AbstractEnumTestCase
{
    public function testValuesExistAndMatch(): void
    {
        $this->assertSame('INIT', TransferDetailStatus::INIT->value);
        $this->assertSame('WAIT_PAY', TransferDetailStatus::WAIT_PAY->value);
        $this->assertSame('PROCESSING', TransferDetailStatus::PROCESSING->value);
        $this->assertSame('SUCCESS', TransferDetailStatus::SUCCESS->value);
        $this->assertSame('FAIL', TransferDetailStatus::FAIL->value);
    }

    public function testGetLabelReturnsCorrectLabel(): void
    {
        $this->assertSame('初始态', TransferDetailStatus::INIT->getLabel());
        $this->assertSame('待确认', TransferDetailStatus::WAIT_PAY->getLabel());
        $this->assertSame('转账中', TransferDetailStatus::PROCESSING->getLabel());
        $this->assertSame('转账成功', TransferDetailStatus::SUCCESS->getLabel());
        $this->assertSame('转账失败', TransferDetailStatus::FAIL->getLabel());
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $result = TransferDetailStatus::INIT->toArray();
        $this->assertSame(['value' => 'INIT', 'label' => '初始态'], $result);

        $result = TransferDetailStatus::WAIT_PAY->toArray();
        $this->assertSame(['value' => 'WAIT_PAY', 'label' => '待确认'], $result);

        $result = TransferDetailStatus::PROCESSING->toArray();
        $this->assertSame(['value' => 'PROCESSING', 'label' => '转账中'], $result);

        $result = TransferDetailStatus::SUCCESS->toArray();
        $this->assertSame(['value' => 'SUCCESS', 'label' => '转账成功'], $result);

        $result = TransferDetailStatus::FAIL->toArray();
        $this->assertSame(['value' => 'FAIL', 'label' => '转账失败'], $result);
    }

    public function testToSelectItemReturnsCorrectStructure(): void
    {
        $result = TransferDetailStatus::INIT->toSelectItem();
        $expected = [
            'label' => '初始态',
            'text' => '初始态',
            'value' => 'INIT',
            'name' => '初始态',
        ];
        $this->assertSame($expected, $result);

        $result = TransferDetailStatus::WAIT_PAY->toSelectItem();
        $expected = [
            'label' => '待确认',
            'text' => '待确认',
            'value' => 'WAIT_PAY',
            'name' => '待确认',
        ];
        $this->assertSame($expected, $result);

        $result = TransferDetailStatus::PROCESSING->toSelectItem();
        $expected = [
            'label' => '转账中',
            'text' => '转账中',
            'value' => 'PROCESSING',
            'name' => '转账中',
        ];
        $this->assertSame($expected, $result);

        $result = TransferDetailStatus::SUCCESS->toSelectItem();
        $expected = [
            'label' => '转账成功',
            'text' => '转账成功',
            'value' => 'SUCCESS',
            'name' => '转账成功',
        ];
        $this->assertSame($expected, $result);

        $result = TransferDetailStatus::FAIL->toSelectItem();
        $expected = [
            'label' => '转账失败',
            'text' => '转账失败',
            'value' => 'FAIL',
            'name' => '转账失败',
        ];
        $this->assertSame($expected, $result);
    }
}
