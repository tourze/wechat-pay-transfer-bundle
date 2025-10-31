<?php

declare(strict_types=1);

namespace WechatPayTransferBundle\Tests\Service;

use Knp\Menu\ItemInterface;
use Knp\Menu\MenuFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;
use WechatPayTransferBundle\Entity\TransferBatch;
use WechatPayTransferBundle\Entity\TransferDetail;
use WechatPayTransferBundle\Service\AdminMenu;

/**
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
final class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    private AdminMenu $adminMenu;

    protected function onSetUp(): void
    {
        $this->adminMenu = self::getService(AdminMenu::class);
    }

    public function testServiceFromContainer(): void
    {
        $this->assertInstanceOf(AdminMenu::class, $this->adminMenu);
        $this->assertInstanceOf(MenuProviderInterface::class, $this->adminMenu);
    }

    public function testCreatesWechatPayMenu(): void
    {
        // 创建根菜单项
        $menuFactory = new MenuFactory();
        $rootItem = $menuFactory->createItem('root');

        // 调用菜单提供器
        ($this->adminMenu)($rootItem);

        // 验证微信支付菜单被创建
        $wechatPayMenu = $rootItem->getChild('微信支付');
        $this->assertInstanceOf(ItemInterface::class, $wechatPayMenu);
        $this->assertSame('fab fa-weixin', $wechatPayMenu->getAttribute('icon'));
    }

    public function testCreatesTransferManagementSubMenu(): void
    {
        // 创建根菜单项
        $menuFactory = new MenuFactory();
        $rootItem = $menuFactory->createItem('root');

        // 调用菜单提供器
        ($this->adminMenu)($rootItem);

        // 验证转账管理子菜单被创建
        $wechatPayMenu = $rootItem->getChild('微信支付');
        $this->assertInstanceOf(ItemInterface::class, $wechatPayMenu);

        $transferMenu = $wechatPayMenu->getChild('转账管理');
        $this->assertInstanceOf(ItemInterface::class, $transferMenu);
        $this->assertSame('fa fa-exchange-alt', $transferMenu->getAttribute('icon'));
    }

    public function testCreatesTransferMenuItems(): void
    {
        // 创建根菜单项
        $menuFactory = new MenuFactory();
        $rootItem = $menuFactory->createItem('root');

        // 调用菜单提供器
        ($this->adminMenu)($rootItem);

        // 获取转账管理菜单
        $wechatPayMenu = $rootItem->getChild('微信支付');
        $this->assertInstanceOf(ItemInterface::class, $wechatPayMenu);

        $transferMenu = $wechatPayMenu->getChild('转账管理');
        $this->assertInstanceOf(ItemInterface::class, $transferMenu);

        // 验证转账批次菜单项
        $transferBatchMenu = $transferMenu->getChild('转账批次');
        $this->assertInstanceOf(ItemInterface::class, $transferBatchMenu);
        $this->assertSame('fa fa-list', $transferBatchMenu->getAttribute('icon'));
        $transferBatchUri = (string) $transferBatchMenu->getUri();
        $this->assertStringContainsString(urlencode(TransferBatch::class), $transferBatchUri);

        // 验证转账明细菜单项
        $transferDetailMenu = $transferMenu->getChild('转账明细');
        $this->assertInstanceOf(ItemInterface::class, $transferDetailMenu);
        $this->assertSame('fa fa-list-ul', $transferDetailMenu->getAttribute('icon'));
        $transferDetailUri = (string) $transferDetailMenu->getUri();
        $this->assertStringContainsString(urlencode(TransferDetail::class), $transferDetailUri);
    }

    public function testHandlesExistingWechatPayMenu(): void
    {
        // 创建根菜单项并预先添加微信支付菜单
        $menuFactory = new MenuFactory();
        $rootItem = $menuFactory->createItem('root');
        $rootItem->addChild('微信支付')->setAttribute('icon', 'existing-icon');

        // 调用菜单提供器
        ($this->adminMenu)($rootItem);

        // 验证不会重复创建微信支付菜单，但会保持原有图标
        $wechatPayMenu = $rootItem->getChild('微信支付');
        $this->assertInstanceOf(ItemInterface::class, $wechatPayMenu);
        $this->assertSame('existing-icon', $wechatPayMenu->getAttribute('icon'));

        // 验证转账管理子菜单仍然被正确创建
        $transferMenu = $wechatPayMenu->getChild('转账管理');
        $this->assertInstanceOf(ItemInterface::class, $transferMenu);
    }
}
