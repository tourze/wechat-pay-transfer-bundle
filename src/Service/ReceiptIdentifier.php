<?php

declare(strict_types=1);

namespace WechatPayTransferBundle\Service;

/**
 * 回单标识符值对象
 *
 * 统一处理商户单号和微信单号的标识符，避免在方法中传递多个参数
 */
class ReceiptIdentifier
{
    private ?string $outBatchNo;
    private ?string $batchId;
    private ?string $outDetailNo;
    private ?string $detailId;
    private string $type;

    public function __construct(
        ?string $outBatchNo = null,
        ?string $batchId = null,
        ?string $outDetailNo = null,
        ?string $detailId = null
    ) {
        $this->outBatchNo = $outBatchNo;
        $this->batchId = $batchId;
        $this->outDetailNo = $outDetailNo;
        $this->detailId = $detailId;

        // 确定标识符类型
        if ($outBatchNo !== null) {
            $this->type = 'OUT_BATCH_NO';
        } elseif ($batchId !== null) {
            $this->type = 'BATCH_ID';
        } else {
            throw new \InvalidArgumentException('必须提供 outBatchNo 或 batchId 中的一个');
        }
    }

    public function getOutBatchNo(): ?string
    {
        return $this->outBatchNo;
    }

    public function getBatchId(): ?string
    {
        return $this->batchId;
    }

    public function getOutDetailNo(): ?string
    {
        return $this->outDetailNo;
    }

    public function getDetailId(): ?string
    {
        return $this->detailId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isOutBatchNoType(): bool
    {
        return $this->type === 'OUT_BATCH_NO';
    }

    public function isBatchIdType(): bool
    {
        return $this->type === 'BATCH_ID';
    }

    public function hasDetailNo(): bool
    {
        return $this->outDetailNo !== null && $this->outDetailNo !== '';
    }

    public function hasDetailId(): bool
    {
        return $this->detailId !== null && $this->detailId !== '';
    }

    /**
     * 构建查询URL
     */
    public function buildQueryUrl(): string
    {
        if ($this->isOutBatchNoType()) {
            $url = "/v3/fund-app/mch-transfer/elecsign/out-bill-no/{$this->outBatchNo}";
            if ($this->hasDetailNo()) {
                $url .= "?out_detail_no={$this->outDetailNo}";
            }
            return $url;
        }

        // BATCH_ID type
        $url = "/v3/fund-app/mch-transfer/elecsign/transfer-bill-no/{$this->batchId}";
        if ($this->hasDetailId()) {
            $url .= "?detail_id={$this->detailId}";
        }
        return $url;
    }

    /**
     * 构建申请请求数据
     * @return array<string, mixed>
     */
    public function buildApplyRequestData(): array
    {
        $requestData = [];

        if ($this->outBatchNo !== null) {
            $requestData['out_batch_no'] = $this->outBatchNo;
        }

        if ($this->batchId !== null) {
            $requestData['batch_id'] = $this->batchId;
        }

        if ($this->hasDetailNo()) {
            $requestData['out_detail_no'] = $this->outDetailNo;
        }

        if ($this->hasDetailId()) {
            $requestData['detail_id'] = $this->detailId;
        }

        return $requestData;
    }

    /**
     * 获取日志数据
     * @return array<string, mixed>
     */
    public function getLogData(): array
    {
        return array_filter([
            'out_batch_no' => $this->outBatchNo,
            'batch_id' => $this->batchId,
            'out_detail_no' => $this->outDetailNo,
            'detail_id' => $this->detailId,
        ], static fn($value) => $value !== null);
    }

    public function __toString(): string
    {
        return match ($this->type) {
            'OUT_BATCH_NO' => $this->outBatchNo ?? '',
            'BATCH_ID' => $this->batchId ?? '',
            'OUT_DETAIL_NO' => $this->outDetailNo ?? '',
            'DETAIL_ID' => $this->detailId ?? '',
            default => '',
        };
    }

    public function isOutBatchNo(): bool
    {
        return $this->type === 'OUT_BATCH_NO';
    }

    public function isBatchId(): bool
    {
        return $this->type === 'BATCH_ID';
    }

    public function isOutDetailNo(): bool
    {
        return $this->outDetailNo !== null;
    }

    public function isDetailId(): bool
    {
        return $this->detailId !== null;
    }

    public function getValue(): string
    {
        return (string) $this;
    }

    public static function forOutBatchNo(string $outBatchNo): self
    {
        return new self($outBatchNo);
    }

    public static function forBatchId(string|int $batchId): self
    {
        return new self(null, (string) $batchId);
    }

    public static function forOutDetailNo(string $outDetailNo): self
    {
        $identifier = new self('dummy');
        $identifier->outDetailNo = $outDetailNo;
        $identifier->type = 'OUT_DETAIL_NO';
        return $identifier;
    }

    public static function forDetailId(string|int $detailId): self
    {
        $identifier = new self('dummy');
        $identifier->detailId = (string) $detailId;
        $identifier->type = 'DETAIL_ID';
        return $identifier;
    }

    public function buildApiUrl(string $operation): string
    {
        $baseUrl = 'https://api.mch.weixin.qq.com/v3/transfer/bill-receipt';

        return match ($operation) {
            'apply', 'query' => match ($this->type) {
                'OUT_BATCH_NO' => "$baseUrl/batch-nos/{$this->outBatchNo}/receipts",
                'BATCH_ID' => "$baseUrl/batch-id/{$this->batchId}/receipts",
                'OUT_DETAIL_NO' => "$baseUrl/detail-nos/{$this->outDetailNo}/receipt",
                'DETAIL_ID' => "$baseUrl/detail-id/{$this->detailId}/receipt",
                default => throw new \InvalidArgumentException("Unsupported type: {$this->type}"),
            },
            default => throw new \InvalidArgumentException("Unsupported operation: {$operation}"),
        };
    }

    /**
     * @return array<string, string|null>
     */
    public function buildRequestData(): array
    {
        return match ($this->type) {
            'OUT_BATCH_NO' => ['out_batch_no' => $this->outBatchNo],
            'BATCH_ID' => ['batch_id' => $this->batchId],
            'OUT_DETAIL_NO' => ['out_detail_no' => $this->outDetailNo],
            'DETAIL_ID' => ['detail_id' => $this->detailId],
            default => [],
        };
    }

    /**
     * @return array<string, string|null>
     */
    public function buildLogData(): array
    {
        return match ($this->type) {
            'OUT_BATCH_NO' => ['out_batch_no' => $this->outBatchNo],
            'BATCH_ID' => ['batch_id' => $this->batchId],
            'OUT_DETAIL_NO' => ['out_detail_no' => $this->outDetailNo],
            'DETAIL_ID' => ['detail_id' => $this->detailId],
            default => [],
        };
    }
}