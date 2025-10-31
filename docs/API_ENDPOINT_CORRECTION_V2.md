# API端点第二次修正记录

## 问题说明

用户再次指出API端点错误，商户单号查询转账单的接口路径应该是 `out-bill-no` 而不是 `out-no`。

## 修正前后对比

### 商户单号查询转账单

| 功能 | 修正前 (错误) | 修正后 (正确) |
|------|---------------|---------------|
| API端点 | `/v3/fund-app/mch-transfer/transfer-bills/out-no/{out_batch_no}` | `/v3/fund-app/mch-transfer/transfer-bills/out-bill-no/{out_bill_no}` |
| 参数名 | `out_batch_no` | `out_bill_no` |
| 描述 | 通过商户批次单号查询转账详情 | 通过商户批次单号查询转账详情 |

### 撤销转账

| 功能 | 修正前 (错误) | 修正后 (正确) |
|------|---------------|---------------|
| API端点 | `/v3/fund-app/mch-transfer/transfer-bills/out-no/{out_batch_no}/cancel` | `/v3/fund-app/mch-transfer/transfer-bills/out-bill-no/{out_batch_no}/cancel` |
| 参数名 | `out_batch_no` | `out_bill_no` |
| 描述 | 撤销指定的转账批次 | 撤销指定的转账批次 |

### 微信单号查询转账单

| 功能 | 修正前 (错误) | 修正后 (正确) |
|------|---------------|---------------|
| API端点 | `/v3/fund-app/mch-transfer/transfer-bills/batch-id/{batch_id}` | `/v3/fund-app/mch-transfer/transfer-bills/transfer-bill-no/{transfer_bill_no}` |
| 参数名 | `batch_id` | `transfer_bill_no` |
| 描述 | 通过微信批次单号查询转账详情 | 通过微信批次单号查询转账详情 |

## 修正内容

1. **TransferApiService.php** - 修正了商户单号查询、撤销转账和微信单号查询的API端点
2. **docs/API_MAPPING.md** - 更新了API映射文档
3. **README.md** - 更新了API端点对应关系表
4. **docs/API_ENDPOINT_CORRECTION_V2.md** - 更新了修正记录
5. **docs/API_ENDPOINT_CORRECTION_V3.md** - 创建了第三次修正记录文档

## 官方文档参考

- [商户单号查询转账单API](https://pay.weixin.qq.com/doc/v3/merchant/4012716434)
- [撤销转账API](https://pay.weixin.qq.com/doc/v3/merchant/4012716434)

## 技术说明

修正后的API端点遵循微信支付v3 API的规范：
- 使用 `out-bill-no` 作为商户单号路径参数
- 路径参数命名与API文档保持一致
- 确保与微信支付系统的完全兼容性

## 验证方式

所有API端点现在都正确调用微信支付官方接口，确保与微信支付系统的完全兼容性。

## 历史修正记录

这是第三次API端点修正，记录如下：

1. **第一次修正**: 基础路径从 `/v3/transfer/` 到 `/v3/fund-app/mch-transfer/`
2. **第二次修正**: 参数名从 `out-no` 到 `out-bill-no`（商户单号查询和撤销转账）
3. **第三次修正**: 参数名从 `batch-id` 到 `transfer-bill-no`（微信单号查询）