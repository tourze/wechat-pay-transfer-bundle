<?php

declare(strict_types=1);

namespace WechatPayTransferBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * 微信支付转账Bundle定时任务配置
 */
class WechatPayTransferCronConfiguration
{
    public static function load(ContainerBuilder $container, ServicesConfigurator $services): void
    {
        // 如果需要，这里可以添加定时任务相关的服务配置
        // 目前命令类已经通过属性自动配置，不需要额外的手动配置
        
        // 示例：如果需要专门的定时任务服务，可以在这里定义
        // $services->set('wechat_pay_transfer.cron_manager', CronManager::class)
        //     ->args([
        //         '$entityManager',
        //         '$transferApiService',
        //         '$receiptApiService',
        //         '$logger',
        //     ]);
    }
}