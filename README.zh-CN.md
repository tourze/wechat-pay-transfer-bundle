# wechat-pay-transfer-bundle

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-blue.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Build Status](https://img.shields.io/badge/build-passing-brightgreen.svg)](#)
[![Coverage](https://img.shields.io/badge/coverage-96%25-brightgreen.svg)](#)

[English](README.md) | [中文](README.zh-CN.md)

微信支付转账功能包，提供批量转账和交易明细管理功能。

## 功能特性

- **批量转账管理**：创建和管理批量转账操作
- **转账明细跟踪**：跟踪批次内的单个转账明细
- **状态管理**：全面的转账状态跟踪
- **Doctrine ORM 集成**：完整的实体映射和存储库支持
- **验证约束**：所有实体属性的内置验证

## 安装

使用 Composer 添加依赖：

```bash
composer require tourze/wechat-pay-transfer-bundle
```

在你的 Symfony 应用程序中启用此包，将其添加到 `config/bundles.php`：

```php
<?php

return [
    // ... 其他包
    WechatPayTransferBundle\WechatPayTransferBundle::class => ['all' => true],
];
```

## 快速开始

### 1. 创建转账批次

```php
<?php

use WechatPayTransferBundle\Entity\TransferBatch;
use WechatPayTransferBundle\Enum\TransferBatchStatus;

$batch = new TransferBatch();
$batch->setOutBatchNo('BATCH_2024_001')
      ->setBatchName('工资转账')
      ->setBatchRemark('月度工资转账')
      ->setTotalAmount(100000) // 金额单位为分
      ->setTotalNum(10)
      ->setBatchStatus(TransferBatchStatus::PROCESSING);

$entityManager->persist($batch);
$entityManager->flush();
```

### 2. 添加转账明细

```php
<?php

use WechatPayTransferBundle\Entity\TransferDetail;
use WechatPayTransferBundle\Enum\TransferDetailStatus;

$detail = new TransferDetail();
$detail->setBatch($batch)
       ->setOutDetailNo('DETAIL_001')
       ->setTransferAmount(10000) // 金额单位为分
       ->setTransferRemark('员工工资')
       ->setOpenid('user_openid_123')
       ->setUserName('张三')
       ->setDetailStatus(TransferDetailStatus::PROCESSING);

$entityManager->persist($detail);
$entityManager->flush();
```

## 使用方法

### 数据库存储库

使用提供的存储库查询转账数据：

```php
<?php

use WechatPayTransferBundle\Repository\TransferBatchRepository;
use WechatPayTransferBundle\Repository\TransferDetailRepository;

// 获取转账批次存储库
$batchRepository = $entityManager->getRepository(TransferBatch::class);

// 根据商家批次单号查找批次
$batch = $batchRepository->findOneBy(['outBatchNo' => 'BATCH_2024_001']);

// 获取转账明细存储库
$detailRepository = $entityManager->getRepository(TransferDetail::class);

// 根据批次查找明细
$details = $detailRepository->findBy(['batch' => $batch]);
```

### 状态管理

该包提供枚举来管理状态：

```php
<?php

use WechatPayTransferBundle\Enum\TransferBatchStatus;
use WechatPayTransferBundle\Enum\TransferDetailStatus;

// 批次状态
TransferBatchStatus::PROCESSING; // 转账中
TransferBatchStatus::FINISHED;   // 已完成
TransferBatchStatus::CLOSED;     // 已关闭

// 明细状态
TransferDetailStatus::INIT;       // 初始态
TransferDetailStatus::WAIT_PAY;   // 待确认
TransferDetailStatus::PROCESSING; // 转账中
TransferDetailStatus::SUCCESS;    // 转账成功
TransferDetailStatus::FAIL;       // 转账失败
```

## 命令行工具

该包提供了多个命令行工具来管理转账和回单数据：

### 转账状态同步

同步转账状态到微信支付服务器：

```bash
# 同步所有待同步的转账批次
php bin/console wechat-pay-transfer:sync-status

# 模拟模式（不实际同步）
php bin/console wechat-pay-transfer:sync-status --dry-run

# 限制处理数量
php bin/console wechat-pay-transfer:sync-status --limit=10
```

### 电子回单状态同步

同步电子回单状态：

```bash
# 同步所有待同步的电子回单
php bin/console wechat-pay-transfer:sync-receipts

# 模拟模式（不实际同步）
php bin/console wechat-pay-transfer:sync-receipts --dry-run

# 限制处理数量
php bin/console wechat-pay-transfer:sync-receipts --limit=5
```

### 批量申请电子回单

为已完成的转账批次批量申请电子回单：

```bash
# 批量申请回单
php bin/console wechat-pay-transfer:batch-apply-receipts
```

### 清理过期数据

清理过期的转账和回单数据：

```bash
# 交互式清理
php bin/console wechat-pay-transfer:cleanup

# 强制执行清理（跳过确认）
php bin/console wechat-pay-transfer:cleanup --force

# 模拟模式（不实际删除）
php bin/console wechat-pay-transfer:cleanup --dry-run
```

## 高级用法

### 自定义查询

使用存储库创建自定义查询：

```php
<?php

$queryBuilder = $batchRepository->createQueryBuilder('b')
    ->leftJoin('b.details', 'd')
    ->where('b.batchStatus = :status')
    ->setParameter('status', TransferBatchStatus::PROCESSING)
    ->orderBy('b.createdAt', 'DESC');

$processingBatches = $queryBuilder->getQuery()->getResult();
```

### 数据验证

所有实体都包含全面的验证约束：

```php
<?php

use Symfony\Component\Validator\Validator\ValidatorInterface;

$violations = $validator->validate($transferBatch);

if (count($violations) > 0) {
    foreach ($violations as $violation) {
        echo $violation->getMessage() . "\n";
    }
}
```

## 配置

该包会自动注册其服务，无需额外配置。

## 依赖项

- PHP 8.1+
- Symfony 6.4+
- Doctrine ORM 3.0+
- tourze/wechat-pay-bundle

## 许可证

本项目采用 MIT 许可证 - 详情请参阅 [LICENSE](LICENSE) 文件。

## 贡献

请在提交拉取请求之前阅读我们的贡献指南。

## 支持

如需支持和问题咨询，请使用问题跟踪器。