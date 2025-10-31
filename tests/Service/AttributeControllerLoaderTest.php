<?php

declare(strict_types=1);

namespace WechatPayTransferBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Symfony\Component\Routing\RouteCollection;
use WechatPayTransferBundle\Service\AttributeControllerLoader;

/**
 * @internal
 */
#[CoversClass(AttributeControllerLoader::class)]
#[RunTestsInSeparateProcesses]
final class AttributeControllerLoaderTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // No setup needed for this test
    }
    public function testAutoloadReturnsRouteCollection(): void
    {
        $loader = self::getService(AttributeControllerLoader::class);
        $collection = $loader->autoload();

        $this->assertInstanceOf(RouteCollection::class, $collection);
        $this->assertNotEmpty($collection->all());
    }

    public function testLoadReturnsRouteCollection(): void
    {
        $loader = self::getService(AttributeControllerLoader::class);
        $collection = $loader->load('some_resource');

        $this->assertInstanceOf(RouteCollection::class, $collection);
        $this->assertNotEmpty($collection->all());
    }

    public function testSupportsReturnsFalse(): void
    {
        $loader = self::getService(AttributeControllerLoader::class);
        $this->assertFalse($loader->supports('some_resource'));
    }

    public function testApiRoutesAreLoaded(): void
    {
        $loader = self::getService(AttributeControllerLoader::class);
        $collection = $loader->autoload();

        // 检查是否包含API路由
        $this->assertTrue($collection->get('api_wechat_pay_transfer') !== null ||
                       $collection->get('api_wechat_pay_transfer_') !== null ||
                       $this->hasRouteWithPattern($collection, 'api_wechat_pay_transfer'));

        $this->assertTrue($collection->get('api_wechat_pay_transfer_receipt') !== null ||
                       $collection->get('api_wechat_pay_transfer_receipt_') !== null ||
                       $this->hasRouteWithPattern($collection, 'api_wechat_pay_transfer_receipt'));
    }

    private function hasRouteWithPattern(RouteCollection $collection, string $pattern): bool
    {
        foreach ($collection->all() as $name => $route) {
            if (str_starts_with($name, $pattern)) {
                return true;
            }
        }
        return false;
    }
}