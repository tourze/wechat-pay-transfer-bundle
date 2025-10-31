<?php

namespace WechatPayTransferBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use WechatPayTransferBundle\Enum\TransferBatchStatus;

/**
 * @internal
 */
#[CoversClass(TransferBatchStatus::class)]
final class TransferBatchStatusTest extends AbstractEnumTestCase
{
    public function testValuesExistAndMatch(): void
    {
        $this->assertSame('PROCESSING', TransferBatchStatus::PROCESSING->value);
        $this->assertSame('FINISHED', TransferBatchStatus::FINISHED->value);
        $this->assertSame('CLOSED', TransferBatchStatus::CLOSED->value);
    }

    public function testGetLabelReturnsCorrectLabel(): void
    {
        $this->assertSame('转账中', TransferBatchStatus::PROCESSING->getLabel());
        $this->assertSame('已完成', TransferBatchStatus::FINISHED->getLabel());
        $this->assertSame('已关闭', TransferBatchStatus::CLOSED->getLabel());
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $result = TransferBatchStatus::PROCESSING->toArray();
        $this->assertSame(['value' => 'PROCESSING', 'label' => '转账中'], $result);

        $result = TransferBatchStatus::FINISHED->toArray();
        $this->assertSame(['value' => 'FINISHED', 'label' => '已完成'], $result);

        $result = TransferBatchStatus::CLOSED->toArray();
        $this->assertSame(['value' => 'CLOSED', 'label' => '已关闭'], $result);
    }

    public function testToSelectItemReturnsCorrectStructure(): void
    {
        $result = TransferBatchStatus::PROCESSING->toSelectItem();
        $expected = [
            'label' => '转账中',
            'text' => '转账中',
            'value' => 'PROCESSING',
            'name' => '转账中',
        ];
        $this->assertSame($expected, $result);

        $result = TransferBatchStatus::FINISHED->toSelectItem();
        $expected = [
            'label' => '已完成',
            'text' => '已完成',
            'value' => 'FINISHED',
            'name' => '已完成',
        ];
        $this->assertSame($expected, $result);

        $result = TransferBatchStatus::CLOSED->toSelectItem();
        $expected = [
            'label' => '已关闭',
            'text' => '已关闭',
            'value' => 'CLOSED',
            'name' => '已关闭',
        ];
        $this->assertSame($expected, $result);
    }
}
