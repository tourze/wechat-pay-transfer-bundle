<?php

declare(strict_types=1);

namespace WechatPayTransferBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use WechatPayTransferBundle\Enum\TransferReceiptStatus;

/**
 * 转账电子回单状态枚举测试
 *
 * 测试TransferReceiptStatus枚举的各种功能，包括状态标签、选择选项和状态判断方法。
 *
 * @see https://pay.weixin.qq.com/doc/v3/merchant/4012716452
 */
#[CoversClass(TransferReceiptStatus::class)]
final class TransferReceiptStatusTest extends AbstractEnumTestCase
{
    public function testAllStatusValues(): void
    {
        $statuses = TransferReceiptStatus::cases();
        
        $this->assertCount(5, $statuses);
        
        $statusValues = array_map(fn($status) => $status->value, $statuses);
        $this->assertContains('GENERATING', $statusValues);
        $this->assertContains('AVAILABLE', $statusValues);
        $this->assertContains('EXPIRED', $statusValues);
        $this->assertContains('FAILED', $statusValues);
        $this->assertContains('DOWNLOADED', $statusValues);
    }

    public function testStatusLabelMapping(): void
    {
        $this->assertEquals('生成中', TransferReceiptStatus::GENERATING->getLabel());
        $this->assertEquals('可用', TransferReceiptStatus::AVAILABLE->getLabel());
        $this->assertEquals('已过期', TransferReceiptStatus::EXPIRED->getLabel());
        $this->assertEquals('生成失败', TransferReceiptStatus::FAILED->getLabel());
        $this->assertEquals('已下载', TransferReceiptStatus::DOWNLOADED->getLabel());
    }

    public function testGetSelectChoices(): void
    {
        $choices = TransferReceiptStatus::getSelectChoices();
        
        $this->assertIsArray($choices);
        $this->assertCount(5, $choices);
        
        $this->assertArrayHasKey('生成中', $choices);
        $this->assertArrayHasKey('可用', $choices);
        $this->assertArrayHasKey('已过期', $choices);
        $this->assertArrayHasKey('生成失败', $choices);
        $this->assertArrayHasKey('已下载', $choices);
        
        $this->assertEquals('GENERATING', $choices['生成中']);
        $this->assertEquals('AVAILABLE', $choices['可用']);
        $this->assertEquals('EXPIRED', $choices['已过期']);
        $this->assertEquals('FAILED', $choices['生成失败']);
        $this->assertEquals('DOWNLOADED', $choices['已下载']);
    }

    public function testGetBadgeColor(): void
    {
        $this->assertEquals('warning', TransferReceiptStatus::GENERATING->getBadgeColor());
        $this->assertEquals('success', TransferReceiptStatus::AVAILABLE->getBadgeColor());
        $this->assertEquals('secondary', TransferReceiptStatus::EXPIRED->getBadgeColor());
        $this->assertEquals('danger', TransferReceiptStatus::FAILED->getBadgeColor());
        $this->assertEquals('info', TransferReceiptStatus::DOWNLOADED->getBadgeColor());
    }

    public function testIsDownloadable(): void
    {
        $this->assertFalse(TransferReceiptStatus::GENERATING->isDownloadable());
        $this->assertTrue(TransferReceiptStatus::AVAILABLE->isDownloadable());
        $this->assertFalse(TransferReceiptStatus::EXPIRED->isDownloadable());
        $this->assertFalse(TransferReceiptStatus::FAILED->isDownloadable());
        $this->assertFalse(TransferReceiptStatus::DOWNLOADED->isDownloadable());
    }

    public function testNeedsReapply(): void
    {
        $this->assertFalse(TransferReceiptStatus::GENERATING->needsReapply());
        $this->assertFalse(TransferReceiptStatus::AVAILABLE->needsReapply());
        $this->assertTrue(TransferReceiptStatus::EXPIRED->needsReapply());
        $this->assertTrue(TransferReceiptStatus::FAILED->needsReapply());
        $this->assertFalse(TransferReceiptStatus::DOWNLOADED->needsReapply());
    }

    public function testStatusFlow(): void
    {
        // 测试正常的状态流转
        $status = TransferReceiptStatus::GENERATING;
        $this->assertFalse($status->isDownloadable());
        $this->assertFalse($status->needsReapply());
        
        $status = TransferReceiptStatus::AVAILABLE;
        $this->assertTrue($status->isDownloadable());
        $this->assertFalse($status->needsReapply());
        
        $status = TransferReceiptStatus::DOWNLOADED;
        $this->assertFalse($status->isDownloadable());
        $this->assertFalse($status->needsReapply());
        
        // 测试异常状态
        $expiredStatus = TransferReceiptStatus::EXPIRED;
        $this->assertFalse($expiredStatus->isDownloadable());
        $this->assertTrue($expiredStatus->needsReapply());
        
        $failedStatus = TransferReceiptStatus::FAILED;
        $this->assertFalse($failedStatus->isDownloadable());
        $this->assertTrue($failedStatus->needsReapply());
    }

    public function testEnumProperties(): void
    {
        $generating = TransferReceiptStatus::GENERATING;
        
        // 测试枚举的基本属性
        $this->assertEquals('GENERATING', $generating->name);
        $this->assertEquals('GENERATING', $generating->value);
        $this->assertEquals('生成中', $generating->getLabel());
        $this->assertEquals('warning', $generating->getBadgeColor());
        
        // 测试状态判断方法
        $this->assertFalse($generating->isDownloadable());
        $this->assertFalse($generating->needsReapply());
    }

    public function testFromValue(): void
    {
        $status = TransferReceiptStatus::from('AVAILABLE');
        $this->assertSame(TransferReceiptStatus::AVAILABLE, $status);
        
        // 测试所有值都可以正确转换
        $this->assertSame(TransferReceiptStatus::GENERATING, TransferReceiptStatus::from('GENERATING'));
        $this->assertSame(TransferReceiptStatus::AVAILABLE, TransferReceiptStatus::from('AVAILABLE'));
        $this->assertSame(TransferReceiptStatus::EXPIRED, TransferReceiptStatus::from('EXPIRED'));
        $this->assertSame(TransferReceiptStatus::FAILED, TransferReceiptStatus::from('FAILED'));
        $this->assertSame(TransferReceiptStatus::DOWNLOADED, TransferReceiptStatus::from('DOWNLOADED'));
    }

    public function testAllCasesHaveUniqueValues(): void
    {
        $cases = TransferReceiptStatus::cases();
        $values = [];
        
        foreach ($cases as $case) {
            $this->assertNotContains($case->value, $values, "Duplicate value found: {$case->value}");
            $values[] = $case->value;
        }
    }

    public function testAllCasesHaveUniqueLabels(): void
    {
        $cases = TransferReceiptStatus::cases();
        $labels = [];

        foreach ($cases as $case) {
            $this->assertNotContains($case->getLabel(), $labels, "Duplicate label found: {$case->getLabel()}");
            $labels[] = $case->getLabel();
        }
    }

    public function testToArray(): void
    {
        $result = TransferReceiptStatus::GENERATING->toArray();
        $this->assertSame(['value' => 'GENERATING', 'label' => '生成中'], $result);

        $result = TransferReceiptStatus::AVAILABLE->toArray();
        $this->assertSame(['value' => 'AVAILABLE', 'label' => '可用'], $result);

        $result = TransferReceiptStatus::EXPIRED->toArray();
        $this->assertSame(['value' => 'EXPIRED', 'label' => '已过期'], $result);

        $result = TransferReceiptStatus::FAILED->toArray();
        $this->assertSame(['value' => 'FAILED', 'label' => '生成失败'], $result);

        $result = TransferReceiptStatus::DOWNLOADED->toArray();
        $this->assertSame(['value' => 'DOWNLOADED', 'label' => '已下载'], $result);
    }
}