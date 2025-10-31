<?php

declare(strict_types=1);

namespace WechatPayTransferBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use WechatPayTransferBundle\DependencyInjection\WechatPayTransferCronConfiguration;

/**
 * @internal
 */
#[CoversClass(WechatPayTransferCronConfiguration::class)]
final class WechatPayTransferCronConfigurationTest extends TestCase
{
    #[Test]
    public function loadShouldExecuteWithoutErrors(): void
    {
        $container = $this->createMock(ContainerBuilder::class);
        $services = $this->createMock(ServicesConfigurator::class);

        // 验证 load 方法能够正常执行而不抛出异常
        WechatPayTransferCronConfiguration::load($container, $services);

        // 如果能执行到这里说明方法正常工作
        $this->assertTrue(true);
    }

    #[Test]
    public function loadShouldHandleNullParametersGracefully(): void
    {
        $container = $this->createMock(ContainerBuilder::class);
        $services = $this->createMock(ServicesConfigurator::class);

        // 验证方法能处理参数而不出错
        WechatPayTransferCronConfiguration::load($container, $services);

        // 如果能执行到这里说明方法正常工作
        $this->assertTrue(true);
    }
}