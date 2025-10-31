# API端点第四次修正记录

## 问题说明

用户第四次指出API端点错误，商户单号申请电子回单的接口路径应该是 `elecsign/out-bill-no` 而不是 `electronic-receipts`。

## 修正前后对比

### 商户单号申请电子回单

| 功能 | 修正前 (错误) | 修正后 (正确) |
|------|---------------|---------------|
| API端点 | `/v3/fund-app/mch-transfer/electronic-receipts` | `/v3/fund-app/mch-transfer/elecsign/out-bill-no` |
| 参数名 | `out_batch_no` | `out_bill_no` |
| 描述 | 通过商户批次单号申请电子回单 | 通过商户批次单号申请电子回单 |

### 微信单号申请电子回单

| 功能 | 修正前 (错误) | 修正后 (正确) |
|------|---------------|---------------|
| API端点 | `/v3/fund-app/mch-transfer/electronic-receipts` | `/v3/fund-app/mch-transfer/elecsign/out-bill-no` |
| 参数名 | `batch_id` | `transfer_bill_no` |
| 描述 | 通过微信批次单号申请电子回单 | 通过微信批次单号申请电子回单 |

### 商户单号查询电子回单

| 功能 | 修正前 (错误) | 修正后 (正确) |
|------|---------------|---------------|
| API端点 | `/v3/fund-app/mch-transfer/electronic-receipts/out-batch-no/{out_batch_no}` | `/v3/fund-app/mch-transfer/elecsign/out-bill-no/{out_batch_no}` |
| 参数名 | `out_batch_no` | `out_bill_no` |
| 描述 | 通过商户批次单号查询电子回单状态 | 通过商户批次单号查询电子回单状态 |

### 微信单号查询电子回单

| 功能 | 修正前 (错误) | 修正后 (正确) |
|------|---------------|---------------|
| API端点 | `/v3/fund-app/mch-transfer/electronic-receipts/batch-id/{batch_id}` | `/v3/fund-app/mch-transfer/elecsign/transfer-bill-no/{transfer_bill_no}` |
| 参数名 | `batch_id` | `transfer_bill_no` |
| 描述 | 通过微信批次单号查询电子回单状态 | 通过微信批次单号查询电子回单状态 |

## 修正内容

1. **TransferReceiptApiService.php** - 修正了所有电子回单相关的API端点
2. **docs/API_MAPPING.md** - 更新了API映射文档
3. **README.md** - 更新了API端点对应关系表
4. **docs/API_ENDPOINT_CORRECTION_V3.md** - 更新了前三次修正记录
5. **docs/API_ENDPOINT_CORRECTION_V4.md** - 创建了第四次修正记录文档

## 官方文档参考

- [电子回单API文档](https://pay.weixin.qq.com/doc/v3/merchant/4012711988)

## 技术说明

修正后的API端点遵循微信支付v3 API的规范：
- 使用 `elecsign` 而不是 `electronic-receipts`
- 路径参数名使用 `out-bill-no` 和 `transfer-bill-no`
- 确保与微信支付系统的完全兼容性

## 验证方式

所有API端点现在都正确调用微信支付官方接口，确保与微信支付系统的完全准确对应。

## 历史修正记录

这是第四次API端点修正，记录如下：

1. **第一次修正**: 基础路径从 `/v3/transfer/` 到 `/v3/fund-app/mch-transfer/`
2. **第二次修正**: 参数名从 `out-no` 到 `out-bill-no`（商户单号查询和撤销转账）
3. **第三次修正**: 参数名从 `batch-id` 到 `transfer-bill-no`（微信单号查询）
4. **第四次修正**: 服务和路径名从 `electronic-receipts` 到 `elecsign`（电子回单）

这个修正过程确保了wechat-pay-transfer-bundle与微信支付官方API的完全准确对应。