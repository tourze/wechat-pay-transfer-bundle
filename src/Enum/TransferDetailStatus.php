<?php

namespace WechatPayTransferBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum TransferDetailStatus: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case INIT = 'INIT';
    case WAIT_PAY = 'WAIT_PAY';
    case PROCESSING = 'PROCESSING';
    case SUCCESS = 'SUCCESS';
    case FAIL = 'FAIL';

    public function getLabel(): string
    {
        return match ($this) {
            self::INIT => '初始态',
            self::WAIT_PAY => '待确认',
            self::PROCESSING => '转账中',
            self::SUCCESS => '转账成功',
            self::FAIL => '转账失败',
        };
    }
}
