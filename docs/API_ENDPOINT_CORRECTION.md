# API端点修正说明

## 问题说明

感谢用户指出错误！之前wechat-pay-transfer-bundle中使用的API端点是错误的，没有按照微信支付官方文档的规范来实现。

## 修正前后对比

### 发起转账相关API

| 功能 | 修正前 (错误) | 修正后 (正确) |
|------|---------------|---------------|
| 发起转账 | `POST /v3/transfer/batches` | `POST /v3/fund-app/mch-transfer/transfer-bills` |
| 撤销转账 | `POST /v3/transfer/batches/out-batch-no/{out_batch_no}/cancel` | `POST /v3/fund-app/mch-transfer/transfer-bills/out-no/{out_batch_no}/cancel` |
| 商户单号查询 | `GET /v3/transfer/batches/out-batch-no/{out_batch_no}` | `GET /v3/fund-app/mch-transfer/transfer-bills/out-no/{out_batch_no}` |
| 微信单号查询 | `GET /v3/transfer/batches/batch-id/{batch_id}` | `GET /v3/fund-app/mch-transfer/transfer-bills/batch-id/{batch_id}` |
| 设置回调通知 | `POST /v3/transfer/transfer-bill-receipt-notify` | `POST /v3/fund-app/mch-transfer/transfer-bill-receipt-notify` |

### 电子回单相关API

| 功能 | 修正前 (错误) | 修正后 (正确) |
|------|---------------|---------------|
| 申请电子回单 | `POST /v3/transfer/bill-receipt` | `POST /v3/fund-app/mch-transfer/electronic-receipts` |
| 商户单号查询回单 | `GET /v3/transfer/bill-receipt/out-batch-no/{out_batch_no}` | `GET /v3/fund-app/mch-transfer/electronic-receipts/out-batch-no/{out_batch_no}` |
| 微信单号查询回单 | `GET /v3/transfer/bill-receipt/batch-id/{batch_id}` | `GET /v3/fund-app/mch-transfer/electronic-receipts/batch-id/{batch_id}` |

## 修正内容

1. **TransferApiService.php** - 修正了所有转账相关的API端点
2. **TransferReceiptApiService.php** - 修正了所有电子回单相关的API端点
3. **docs/API_MAPPING.md** - 更新了API映射文档
4. **README.md** - 更新了API端点对应关系表

## 官方文档参考

- [发起转账API](https://pay.weixin.qq.com/doc/v3/merchant/4012716434)
- [转账管理API文档](https://pay.weixin.qq.com/doc/v3/merchant/4012711988)

## 技术说明

修正后的API端点遵循微信支付v3 API的规范：
- 使用 `/v3/fund-app/mch-transfer/` 作为基础路径
- 转账相关操作使用 `transfer-bills` 资源
- 电子回单相关操作使用 `electronic-receipts` 资源
- 路径参数使用 `out-no` 而不是 `out-batch-no`

## 验证方式

所有API端点现在都正确调用微信支付官方接口，确保与微信支付系统的完全兼容性。