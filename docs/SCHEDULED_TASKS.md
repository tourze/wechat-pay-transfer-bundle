# 定时任务使用指南

## 概述

wechat-pay-transfer-bundle 提供了一系列定时任务命令，用于自动化管理微信支付转账业务，确保数据一致性和系统稳定性。

## 定时任务列表

### 1. 转账状态同步命令

**命令**: `php bin/console wechat-pay-transfer:sync-status`

**功能**: 同步转账状态到微信支付服务器，确保本地数据与官方数据一致。

**使用场景**:
- 异步通知丢失时的状态补偿
- 定期数据一致性检查
- 手动状态同步

**常用选项**:
```bash
# 同步所有处理中的转账
php bin/console wechat-pay-transfer:sync-status

# 只同步特定状态的转账
php bin/console wechat-pay-transfer:sync-status --status=PROCESSING

# 限制每次处理的数量
php bin/console wechat-pay-transfer:sync-status --batch-limit=10 --detail-limit=50

# 强制更新本地状态
php bin/console wechat-pay-transfer:sync-status --force-update
```

### 2. 电子回单状态同步命令

**命令**: `php bin/console wechat-pay-transfer:sync-receipts`

**功能**: 同步电子回单状态到微信支付服务器，确保回单状态及时更新。

**使用场景**:
- 检查正在生成的回单状态
- 自动下载已完成的回单
- 处理生成失败的回单

**常用选项**:
```bash
# 同步所有生成中的回单
php bin/console wechat-pay-transfer:sync-receipts

# 同步特定批次的回单
php bin/console wechat-pay-transfer:sync-receipts --out-batch-no=BATCH_001

# 限制处理数量并自动下载
php bin/console wechat-pay-transfer:sync-receipts --limit=10 --auto-download
```

### 3. 批量申请电子回单命令

**命令**: `php bin/console wechat-pay-transfer:batch-apply-receipts`

**功能**: 批量为已完成的转账申请电子回单，确保所有转账都有对应的电子凭证。

**使用场景**:
- 批量申请回单
- 自动化回单管理
- 财务审计支持

**使用示例**:
```bash
# 批量申请所有未申请回单的转账
php bin/console wechat-pay-transfer:batch-apply-receipts
```

### 4. 清理过期数据命令

**命令**: `php bin/console wechat-pay-transfer:cleanup`

**功能**: 清理过期的电子回单数据，释放存储空间并保持数据库整洁。

**使用场景**:
- 定期清理过期数据
- 数据库空间优化
- 数据生命周期管理

**常用选项**:
```bash
# 查看将要清理的数据（安全模式）
php bin/console wechat-pay-transfer:cleanup --dry-run

# 清理30天前的数据
php bin/console wechat-pay-transfer:cleanup --days=30

# 清理90天前的回单数据
php bin/console wechat-pay-transfer:cleanup --receipt-days=90

# 强制执行不询问确认
php bin/console wechat-pay-transfer:cleanup --force
```

## 推荐的定时任务配置

### Linux Cron 配置示例

```bash
# 每5分钟同步转账状态
*/5 * * * * php /path/to/your/project/bin/console wechat-pay-transfer:sync-status --batch-limit=20 --detail-limit=100 >> /var/log/transfer-sync.log 2>&1

# 每10分钟同步电子回单状态
*/10 * * * * php /path/to/your/project/bin/console wechat-pay-transfer:sync-receipts --limit=50 --auto-download >> /var/log/receipt-sync.log 2>&1

# 每天凌晨2点批量申请回单
0 2 * * * php /path/to/your/project/bin/console wechat-pay-transfer:batch-apply-receipts >> /var/log/batch-apply.log 2>&1

# 每天凌晨3点清理过期数据
0 3 * * * php /path/to/your/project/bin/console wechat-pay-transfer:cleanup --days=30 --receipt-days=90 --force >> /var/log/cleanup.log 2>&1
```

### Symfony Scheduler 配置示例

如果使用 Symfony Scheduler Bundle，可以这样配置：

```yaml
# config/packages/scheduler.yaml
framework:
    scheduler:
        # 配置调度器
        workers:
            auto_start: true

# config/packages/transfer_schedules.yaml
schedules:
    transfer_sync:
        command: 'php bin/console wechat-pay-transfer:sync-status --batch-limit=20'
        schedule: '*/5 * * * *'
        description: '同步转账状态'
        
    receipt_sync:
        command: 'php bin/console wechat-pay-transfer:sync-receipts --limit=50'
        schedule: '*/10 * * * *'
        description: '同步电子回单状态'
        
    batch_apply_receipts:
        command: 'php bin/console wechat-pay-transfer:batch-apply-receipts'
        schedule: '0 2 * * *'
        description: '批量申请电子回单'
        
    cleanup_data:
        command: 'php bin/console wechat-pay-transfer:cleanup --days=30 --receipt-days=90 --force'
        schedule: '0 3 * * *'
        description: '清理过期数据'
```

## 日志配置

建议为定时任务配置专门的日志文件：

```yaml
# config/packages/monolog.yaml
monolog:
    handlers:
        transfer_sync:
            type: stream
            path: "%kernel.logs_dir%/transfer_sync.log"
            level: info
            channels: ['transfer_sync']
            
        receipt_sync:
            type: stream
            path: "%kernel.logs_dir%/receipt_sync.log"
            level: info
            channels: ['receipt_sync']
            
        cleanup:
            type: stream
            path: "%kernel.logs_dir%/cleanup.log"
            level: info
            channels: ['cleanup']
```

## 监控和告警

### 1. 日志监控

监控定时任务的执行日志，及时发现异常：

```bash
# 查看最近的同步日志
tail -f /var/log/transfer-sync.log

# 监控错误日志
grep -i error /var/log/transfer-sync.log
```

### 2. 数据库监控

定期检查数据库中的数据状态：

```sql
-- 查看长时间处于处理中的转账批次
SELECT out_batch_no, batch_status, create_time, update_time 
FROM wechat_payment_transfer_batch 
WHERE batch_status = 'PROCESSING' 
AND update_time < NOW() - INTERVAL '1 DAY';

-- 查看生成失败的电子回单
SELECT apply_no, out_batch_no, receipt_status, apply_time 
FROM wechat_payment_transfer_receipt 
WHERE receipt_status = 'FAILED';
```

### 3. 性能监控

监控API调用频率和响应时间：

```bash
# 监控API调用日志
grep "API调用" /var/log/transfer-sync.log | tail -20
```

## 故障排除

### 常见问题

1. **命令执行失败**
   - 检查微信支付配置是否正确
   - 确认网络连接正常
   - 查看命令输出的错误信息

2. **数据不同步**
   - 检查异步通知是否正常接收
   - 手动执行同步命令
   - 查看微信支付回调日志

3. **回单生成失败**
   - 检查转账是否已完成
   - 确认转账金额和参数正确
   - 联系微信支付技术支持

### 调试技巧

```bash
# 使用详细模式执行命令
php bin/console wechat-pay-transfer:sync-status -v

# 模拟运行查看影响范围
php bin/console wechat-pay-transfer:cleanup --dry-run
```

## 最佳实践

1. **合理设置执行频率**：避免过于频繁的API调用
2. **限制处理数量**：防止单次处理过多数据导致超时
3. **记录详细日志**：便于问题排查和性能分析
4. **定期监控**：及时发现和解决问题
5. **备份重要数据**：清理前确保数据已备份
6. **测试环境验证**：生产环境操作前在测试环境验证