<?php

declare(strict_types=1);

namespace WechatPayTransferBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;
use WechatPayTransferBundle\DependencyInjection\WechatPayTransferExtension;

/**
 * @internal
 */
#[CoversClass(WechatPayTransferExtension::class)]
final class WechatPayTransferExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
}
