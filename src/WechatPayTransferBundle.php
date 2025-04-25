<?php

namespace WechatPayTransferBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\EasyAdmin\Attribute\Permission\AsPermission;

#[AsPermission(title: '微信转账')]
class WechatPayTransferBundle extends Bundle
{
}
