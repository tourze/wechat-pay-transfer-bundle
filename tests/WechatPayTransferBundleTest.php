<?php

declare(strict_types=1);

namespace WechatPayTransferBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;
use WechatPayTransferBundle\WechatPayTransferBundle;

/**
 * @internal
 */
#[CoversClass(WechatPayTransferBundle::class)]
#[RunTestsInSeparateProcesses]
final class WechatPayTransferBundleTest extends AbstractBundleTestCase
{
}
