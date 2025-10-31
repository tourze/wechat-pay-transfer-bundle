# wechat-pay-transfer-bundle

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-blue.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Build Status](https://img.shields.io/badge/build-passing-brightgreen.svg)](#)
[![Coverage](https://img.shields.io/badge/coverage-96%25-brightgreen.svg)](#)

[English](README.md) | [中文](README.zh-CN.md)

A Symfony bundle for WeChat Pay Transfer functionality, providing batch transfer and transaction detail management.

## Features

- **Batch Transfer Management**: Create and manage batch transfer operations
- **Transfer Detail Tracking**: Track individual transfer details within batches
- **Status Management**: Comprehensive status tracking for transfers
- **Doctrine ORM Integration**: Full entity mapping and repository support
- **Validation Constraints**: Built-in validation for all entity properties

## Installation

Add the dependency via Composer:

```bash
composer require tourze/wechat-pay-transfer-bundle
```

Enable the bundle in your Symfony application by adding it to `config/bundles.php`:

```php
<?php

return [
    // ... other bundles
    WechatPayTransferBundle\WechatPayTransferBundle::class => ['all' => true],
];
```

## Quick Start

### 1. Create a Transfer Batch

```php
<?php

use WechatPayTransferBundle\Entity\TransferBatch;
use WechatPayTransferBundle\Enum\TransferBatchStatus;

$batch = new TransferBatch();
$batch->setOutBatchNo('BATCH_2024_001')
      ->setBatchName('Salary Transfer')
      ->setBatchRemark('Monthly salary transfer')
      ->setTotalAmount(100000) // Amount in cents
      ->setTotalNum(10)
      ->setBatchStatus(TransferBatchStatus::PROCESSING);

$entityManager->persist($batch);
$entityManager->flush();
```

### 2. Add Transfer Details

```php
<?php

use WechatPayTransferBundle\Entity\TransferDetail;
use WechatPayTransferBundle\Enum\TransferDetailStatus;

$detail = new TransferDetail();
$detail->setBatch($batch)
       ->setOutDetailNo('DETAIL_001')
       ->setTransferAmount(10000) // Amount in cents
       ->setTransferRemark('Employee salary')
       ->setOpenid('user_openid_123')
       ->setUserName('John Doe')
       ->setDetailStatus(TransferDetailStatus::PROCESSING);

$entityManager->persist($detail);
$entityManager->flush();
```

## Usage

### Repositories

Use the provided repositories to query transfer data:

```php
<?php

use WechatPayTransferBundle\Repository\TransferBatchRepository;
use WechatPayTransferBundle\Repository\TransferDetailRepository;

// Get transfer batch repository
$batchRepository = $entityManager->getRepository(TransferBatch::class);

// Find batch by out batch number
$batch = $batchRepository->findOneBy(['outBatchNo' => 'BATCH_2024_001']);

// Get transfer detail repository
$detailRepository = $entityManager->getRepository(TransferDetail::class);

// Find details by batch
$details = $detailRepository->findBy(['batch' => $batch]);
```

### Status Management

The bundle provides enums for status management:

```php
<?php

use WechatPayTransferBundle\Enum\TransferBatchStatus;
use WechatPayTransferBundle\Enum\TransferDetailStatus;

// Batch statuses
TransferBatchStatus::PROCESSING; // Processing
TransferBatchStatus::FINISHED;   // Finished
TransferBatchStatus::CLOSED;     // Closed

// Detail statuses
TransferDetailStatus::INIT;       // Initial
TransferDetailStatus::WAIT_PAY;   // Waiting for payment
TransferDetailStatus::PROCESSING; // Processing
TransferDetailStatus::SUCCESS;    // Success
TransferDetailStatus::FAIL;       // Failed
```

## Console Commands

The bundle provides several console commands to manage transfers and receipts:

### Transfer Status Synchronization

Synchronize transfer status with WeChat Pay server:

```bash
# Synchronize all pending transfer batches
php bin/console wechat-pay-transfer:sync-status

# Dry run mode (no actual synchronization)
php bin/console wechat-pay-transfer:sync-status --dry-run

# Limit processing count
php bin/console wechat-pay-transfer:sync-status --limit=10
```

### Receipt Status Synchronization

Synchronize electronic receipt status:

```bash
# Synchronize all pending electronic receipts
php bin/console wechat-pay-transfer:sync-receipts

# Dry run mode (no actual synchronization)
php bin/console wechat-pay-transfer:sync-receipts --dry-run

# Limit processing count
php bin/console wechat-pay-transfer:sync-receipts --limit=5
```

### Batch Apply Electronic Receipts

Apply electronic receipts for completed transfer batches:

```bash
# Batch apply receipts
php bin/console wechat-pay-transfer:batch-apply-receipts
```

### Clean Up Expired Data

Clean up expired transfer and receipt data:

```bash
# Interactive cleanup
php bin/console wechat-pay-transfer:cleanup

# Force cleanup (skip confirmation)
php bin/console wechat-pay-transfer:cleanup --force

# Dry run mode (no actual deletion)
php bin/console wechat-pay-transfer:cleanup --dry-run
```

## Advanced Usage

### Custom Queries

Create custom queries using the repositories:

```php
<?php

$queryBuilder = $batchRepository->createQueryBuilder('b')
    ->leftJoin('b.details', 'd')
    ->where('b.batchStatus = :status')
    ->setParameter('status', TransferBatchStatus::PROCESSING)
    ->orderBy('b.createdAt', 'DESC');

$processingBatches = $queryBuilder->getQuery()->getResult();
```

### Validation

All entities include comprehensive validation constraints:

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

## Configuration

The bundle automatically registers its services. No additional configuration is required.

## Dependencies

- PHP 8.1+
- Symfony 6.4+
- Doctrine ORM 3.0+
- tourze/wechat-pay-bundle

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Contributing

Please read our contributing guidelines before submitting pull requests.

## Support

For support and questions, please use the issue tracker.