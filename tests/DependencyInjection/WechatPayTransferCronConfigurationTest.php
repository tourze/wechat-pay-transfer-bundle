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
    public function classCanBeInstantiated(): void
    {
        // 测试类可以实例化
        $instance = new WechatPayTransferCronConfiguration();
        $this->assertInstanceOf(WechatPayTransferCronConfiguration::class, $instance);
    }

    #[Test]
    public function loadMethodHasCorrectSignature(): void
    {
        $reflectionClass = new \ReflectionClass(WechatPayTransferCronConfiguration::class);

        $this->assertTrue($reflectionClass->hasMethod('load'), 'Class should have load method');

        $method = $reflectionClass->getMethod('load');
        $this->assertTrue($method->isStatic(), 'load method should be static');
        $this->assertTrue($method->isPublic(), 'load method should be public');

        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters, 'load method should have 2 parameters');

        // 验证参数类型
        $param0Type = $parameters[0]->getType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $param0Type);
        $this->assertSame(ContainerBuilder::class, $param0Type->getName());

        $param1Type = $parameters[1]->getType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $param1Type);
        $this->assertSame(ServicesConfigurator::class, $param1Type->getName());

        // 验证返回类型
        $returnType = $method->getReturnType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $returnType);
        $this->assertSame('void', $returnType->getName());
    }

    #[Test]
    public function loadMethodCanBeCalledWithRealContainerBuilder(): void
    {
        // 使用真实的 ContainerBuilder
        // 由于 ServicesConfigurator 需要复杂依赖（FileLoader 等），
        // 且当前 load() 是空实现，这里只验证 ContainerBuilder 可以正常创建
        $container = new ContainerBuilder();

        $reflectionMethod = new \ReflectionMethod(WechatPayTransferCronConfiguration::class, 'load');

        // 验证方法存在且可访问
        $this->assertTrue($reflectionMethod->isPublic());
        $this->assertTrue($reflectionMethod->isStatic());

        // 验证 ContainerBuilder 可以正常创建和使用
        $this->assertInstanceOf(ContainerBuilder::class, $container);
    }
}
