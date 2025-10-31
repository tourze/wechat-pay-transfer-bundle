# 微信支付商家转账API映射文档

## 概述

本文档详细说明了wechat-pay-transfer-bundle中实现的微信支付官方API调用映射关系。

## 发起转账类API

### 1. 发起转账
- **Bundle方法**: `TransferApiService::initiateTransfer()`
- **官方API**: `POST /v3/fund-app/mch-transfer/transfer-bills`
- **描述**: 向微信支付发起批量转账请求
- **状态**: ✅ **已实现** - 调用官方API

### 2. APP调起用户确认收款
- **Bundle方法**: `TransferApiService::generateAppConfirmParameters()`
- **官方API**: 生成APP调起参数（非HTTP API）
- **描述**: 生成APP调起用户确认收款的参数
- **状态**: ✅ **已实现** - 参数生成

### 3. JSAPI调起用户确认收款
- **Bundle方法**: `TransferApiService::generateJsApiConfirmParameters()`
- **官方API**: 生成JSAPI调起参数（非HTTP API）
- **描述**: 生成JSAPI调起用户确认收款的参数
- **状态**: ✅ **已实现** - 参数生成

### 4. 撤销转账
- **Bundle方法**: `TransferApiService::cancelTransfer()`
- **官方API**: `POST /v3/fund-app/mch-transfer/transfer-bills/out-no/{out_bill_no}/cancel`
- **描述**: 撤销指定的转账批次
- **状态**: ✅ **已实现** - 调用官方API

### 5. 商户单号查询转账单
- **Bundle方法**: `TransferApiService::queryTransferByOutBatchNo()`
- **官方API**: `GET /v3/fund-app/mch-transfer/transfer-bills/out-bill-no/{out_bill_no}`
- **描述**: 通过商户批次单号查询转账详情
- **状态**: ✅ **已实现** - 调用官方API

### 6. 微信单号查询转账单
- **Bundle方法**: `TransferApiService::queryTransferByBatchId()`
- **官方API**: `GET /v3/fund-app/mch-transfer/transfer-bills/transfer-bill-no/{transfer_bill_no}`
- **描述**: 通过微信批次单号查询转账详情
- **状态**: ✅ **已实现** - 调用官方API

### 7. 上架转账回调通知
- **Bundle方法**: `TransferApiService::setupTransferNotification()`
- **官方API**: `POST /v3/fund-app/mch-transfer/transfer-bill-receipt-notify`
- **描述**: 设置转账回调通知URL
- **状态**: ✅ **已实现** - 调用官方API

## 获取电子回单类API

### 1. 商户单号申请电子回单
- **Bundle方法**: `TransferReceiptApiService::applyReceiptByOutBatchNo()`
- **官方API**: `POST /v3/fund-app/mch-transfer/electronic-receipts`
- **描述**: 通过商户批次单号申请电子回单
- **状态**: ✅ **已实现** - 调用官方API

### 2. 微信单号申请电子回单
- **Bundle方法**: `TransferReceiptApiService::applyReceiptByBatchId()`
- **官方API**: `POST /v3/fund-app/mch-transfer/elecsign/out-bill-no`
- **描述**: 通过微信批次单号申请电子回单
- **状态**: ✅ **已实现** - 调用官方API

### 3. 商户单号查询电子回单
- **Bundle方法**: `TransferReceiptApiService::queryReceiptByOutBatchNo()`
- **官方API**: `GET /v3/fund-app/mch-transfer/electronic-receipts/out-batch-no/{out_batch_no}`
- **描述**: 通过商户批次单号查询电子回单状态
- **状态**: ✅ **已实现** - 调用官方API

### 4. 微信单号查询电子回单
- **Bundle方法**: `TransferReceiptApiService::queryReceiptByBatchId()`
- **官方API**: `GET /v3/fund-app/mch-transfer/electronic-receipts/batch-id/{batch_id}`
- **描述**: 通过微信批次单号查询电子回单状态
- **状态**: ✅ **已实现** - 调用官方API

### 5. 下载电子回单
- **Bundle方法**: `TransferReceiptApiService::downloadReceipt()`
- **官方API**: 使用官方API返回的下载URL
- **描述**: 通过下载URL下载电子回单文件
- **状态**: ✅ **已实现** - 调用下载URL

## 实现状态总结

### ✅ 完全实现的功能
- [x] **发起转账** - `POST /v3/fund-app/mch-transfer/transfer-bills`
- [x] **撤销转账** - `POST /v3/fund-app/mch-transfer/transfer-bills/out-no/{out_batch_no}/cancel`
- [x] **商户单号查询转账单** - `GET /v3/fund-app/mch-transfer/transfer-bills/out-bill-no/{out_bill_no}`
- [x] **微信单号查询转账单** - `GET /v3/fund-app/mch-transfer/transfer-bills/transfer-bill-no/{transfer_bill_no}`
- [x] **上架转账回调通知** - `POST /v3/fund-app/mch-transfer/transfer-bill-receipt-notify`
- [x] **商户单号申请电子回单** - `POST /v3/fund-app/mch-transfer/electronic-receipts`
- [x] **微信单号申请电子回单** - `POST /v3/fund-app/mch-transfer/electronic-receipts`
- [x] **商户单号查询电子回单** - `GET /v3/fund-app/mch-transfer/electronic-receipts/out-batch-no/{out_batch_no}`
- [x] **微信单号查询电子回单** - `GET /v3/fund-app/mch-transfer/electronic-receipts/batch-id/{batch_id}`
- [x] **下载电子回单** - 使用官方下载URL

### 📋 参数生成功能
- [x] **APP调起用户确认收款** - 生成APP调用参数
- [x] **JSAPI调起用户确认收款** - 生成JSAPI调用参数
- [x] **转账回调通知处理** - 处理微信支付回调数据

### 🔄 数据同步功能
- [x] **本地数据更新** - 根据官方API响应更新本地数据库
- [x] **状态同步** - 实时同步转账和回单状态
- [x] **错误处理** - 完整的异常处理和日志记录

## 使用示例

### 发起转账
```php
use WechatPayTransferBundle\Service\TransferApiService;

// 调用微信支付官方API发起转账
$result = $transferApiService->initiateTransfer($transferBatch);
```

### 申请电子回单
```php
use WechatPayTransferBundle\Service\TransferReceiptApiService;

// 调用微信支付官方API申请电子回单
$receipt = $receiptApiService->applyReceiptByOutBatchNo('BATCH_2024_001');
```

### 查询转账状态
```php
// 调用微信支付官方API查询转账
$result = $transferApiService->queryTransferByOutBatchNo('BATCH_2024_001', true);
```

## 总结

wechat-pay-transfer-bundle现在已经完整实现了微信支付商家转账API的所有官方接口调用，包括：

1. **发起转账类API** (7个功能) - 全部实现
2. **获取电子回单类API** (5个功能) - 全部实现
3. **参数生成和回调处理** - 完整支持
4. **数据同步和状态管理** - 完整支持

所有API调用都直接与微信支付官方接口交互，确保了功能的完整性和准确性。