<?php

declare(strict_types=1);

namespace WechatPayTransferBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 转账电子回单状态枚举
 * 
 * 定义转账电子回单的各种状态，用于跟踪回单的生成和可用性状态。
 */
enum TransferReceiptStatus: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    /**
     * 生成中
     * 电子回单正在生成过程中，需要等待一段时间后再查询。
     */
    case GENERATING = 'GENERATING';

    /**
     * 可用
     * 电子回单已生成完成，可以通过下载URL获取回单文件。
     */
    case AVAILABLE = 'AVAILABLE';

    /**
     * 已过期
     * 电子回单已过期，无法再下载，需要重新申请。
     */
    case EXPIRED = 'EXPIRED';

    /**
     * 生成失败
     * 电子回单生成失败，需要检查失败原因并重新申请。
     */
    case FAILED = 'FAILED';

    /**
     * 已下载
     * 电子回单已被下载，用于内部跟踪。
     */
    case DOWNLOADED = 'DOWNLOADED';

    public function getLabel(): string
    {
        return match ($this) {
            self::GENERATING => '生成中',
            self::AVAILABLE => '可用',
            self::EXPIRED => '已过期',
            self::FAILED => '生成失败',
            self::DOWNLOADED => '已下载',
        };
    }

    /**
     * 获取用于EasyAdmin选择字段的选项数组
     *
     * @return array<string, string>
     */
    public static function getSelectChoices(): array
    {
        $choices = [];
        foreach (self::cases() as $case) {
            $choices[$case->getLabel()] = $case->value;
        }

        return $choices;
    }

    /**
     * 获取状态对应的徽章颜色
     */
    public function getBadgeColor(): string
    {
        return match ($this) {
            self::GENERATING => 'warning',
            self::AVAILABLE => 'success',
            self::EXPIRED => 'secondary',
            self::FAILED => 'danger',
            self::DOWNLOADED => 'info',
        };
    }

    /**
     * 检查是否可以下载回单
     */
    public function isDownloadable(): bool
    {
        return $this === self::AVAILABLE;
    }

    /**
     * 检查是否需要重新申请
     */
    public function needsReapply(): bool
    {
        return in_array($this, [self::EXPIRED, self::FAILED], true);
    }
}