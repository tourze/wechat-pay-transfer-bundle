<?php

namespace WechatPayTransferBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum TransferBatchStatus: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case PROCESSING = 'PROCESSING';
    case FINISHED = 'FINISHED';
    case CLOSED = 'CLOSED';

    public function getLabel(): string
    {
        return match ($this) {
            self::PROCESSING => '转账中',
            self::FINISHED => '已完成',
            self::CLOSED => '已关闭',
        };
    }
}
