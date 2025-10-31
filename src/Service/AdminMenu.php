<?php

declare(strict_types=1);

namespace WechatPayTransferBundle\Service;

use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use WechatPayTransferBundle\Entity\TransferBatch;
use WechatPayTransferBundle\Entity\TransferDetail;

#[Autoconfigure(public: true)]
readonly class AdminMenu implements MenuProviderInterface
{
    public function __construct(private LinkGeneratorInterface $linkGenerator)
    {
    }

    public function __invoke(ItemInterface $item): void
    {
        // 创建微信支付顶级菜单
        if (null === $item->getChild('微信支付')) {
            $item->addChild('微信支付')
                ->setAttribute('icon', 'fab fa-weixin')
            ;
        }

        $wechatPayMenu = $item->getChild('微信支付');
        if (null === $wechatPayMenu) {
            return;
        }

        // 创建转账管理子菜单
        if (null === $wechatPayMenu->getChild('转账管理')) {
            $wechatPayMenu->addChild('转账管理')
                ->setAttribute('icon', 'fa fa-exchange-alt')
            ;
        }

        $transferMenu = $wechatPayMenu->getChild('转账管理');
        if (null === $transferMenu) {
            return;
        }

        // 转账批次管理
        $transferMenu
            ->addChild('转账批次')
            ->setUri($this->linkGenerator->getCurdListPage(TransferBatch::class))
            ->setAttribute('icon', 'fa fa-list')
        ;

        // 转账明细管理
        $transferMenu
            ->addChild('转账明细')
            ->setUri($this->linkGenerator->getCurdListPage(TransferDetail::class))
            ->setAttribute('icon', 'fa fa-list-ul')
        ;
    }
}
