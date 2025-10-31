<?php

declare(strict_types=1);

namespace WechatPayTransferBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;
use WechatPayTransferBundle\Enum\TransferReceiptStatus;
use WechatPayTransferBundle\Repository\TransferReceiptRepository;

/**
 * 转账电子回单
 * 
 * 微信支付商家转账电子回单实体，用于存储和管理转账交易的电子凭证信息。
 * 
 * @see https://pay.weixin.qq.com/doc/v3/merchant/4012716452
 */
#[ORM\Entity(repositoryClass: TransferReceiptRepository::class)]
#[ORM\Table(name: 'wechat_payment_transfer_receipt', options: ['comment' => '转账电子回单'])]
class TransferReceipt implements \Stringable
{
    use SnowflakeKeyAware;
    use TimestampableAware;
    use BlameableAware;

    #[ORM\ManyToOne(cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false, options: ['comment' => '关联转账批次'])]
    private ?TransferBatch $transferBatch = null;

    #[ORM\ManyToOne(inversedBy: 'receipts', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false, options: ['comment' => '关联转账明细'])]
    private ?TransferDetail $transferDetail = null;

    /**
     * @var string|null 商户系统内部的转账批次单号
     */
    #[ORM\Column(length: 32, nullable: true, options: ['comment' => '商户批次单号'])]
    #[Assert\Length(max: 32)]
    #[Assert\Regex(pattern: '/^[a-zA-Z0-9_-]+$/', message: '商户批次单号只能包含字母、数字、下划线和横线')]
    private ?string $outBatchNo = null;

    /**
     * @var string|null 商户系统内部的转账明细单号
     */
    #[ORM\Column(length: 32, nullable: true, options: ['comment' => '商户明细单号'])]
    #[Assert\Length(max: 32)]
    #[Assert\Regex(pattern: '/^[a-zA-Z0-9_-]+$/', message: '商户明细单号只能包含字母、数字、下划线和横线')]
    private ?string $outDetailNo = null;

    /**
     * @var string|null 微信支付系统返回的批次单号
     */
    #[ORM\Column(length: 64, nullable: true, options: ['comment' => '微信批次单号'])]
    #[Assert\Length(max: 64)]
    #[Assert\Regex(pattern: '/^[a-zA-Z0-9_-]+$/', message: '微信批次单号格式不正确')]
    private ?string $batchId = null;

    /**
     * @var string|null 微信支付系统返回的明细单号
     */
    #[ORM\Column(length: 64, nullable: true, options: ['comment' => '微信明细单号'])]
    #[Assert\Length(max: 64)]
    #[Assert\Regex(pattern: '/^[a-zA-Z0-9_-]+$/', message: '微信明细单号格式不正确')]
    private ?string $detailId = null;

    /**
     * @var string|null 电子回单类型，如：TRANSACTION_DETAIL（交易明细）
     */
    #[ORM\Column(length: 32, nullable: true, options: ['comment' => '电子回单类型'])]
    #[Assert\Length(max: 32)]
    #[Assert\Choice(choices: ['TRANSACTION_DETAIL'], message: '电子回单类型必须是有效的微信支付类型')]
    private ?string $receiptType = null;

    /**
     * @var TransferReceiptStatus|null 回单状态，如：GENERATING（生成中）、AVAILABLE（可用）、EXPIRED（已过期）、FAILED（生成失败）
     */
    #[ORM\Column(length: 20, nullable: true, enumType: TransferReceiptStatus::class, options: ['comment' => '回单状态'])]
    #[Assert\NotNull(message: '回单状态不能为空')]
    #[Assert\Choice(callback: [TransferReceiptStatus::class, 'cases'])]
    private ?TransferReceiptStatus $receiptStatus = TransferReceiptStatus::GENERATING;

    /**
     * @var string|null 电子回单文件的下载URL
     */
    #[ORM\Column(length: 2048, nullable: true, options: ['comment' => '回单下载地址'])]
    #[Assert\Length(max: 2048)]
    #[Assert\Url(message: '下载地址必须是有效的URL')]
    private ?string $downloadUrl = null;

    /**
     * @var string|null 电子回单文件的哈希值，用于校验文件完整性
     */
    #[ORM\Column(length: 128, nullable: true, options: ['comment' => '回单文件哈希'])]
    #[Assert\Length(max: 128)]
    #[Assert\Regex(pattern: '/^[a-fA-F0-9]+$/', message: '哈希值只能包含十六进制字符')]
    private ?string $hashValue = null;

    /**
     * @var \DateTimeImmutable|null 电子回单文件的生成时间
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '回单生成时间'])]
    #[Assert\Type(type: \DateTimeImmutable::class)]
    private ?\DateTimeImmutable $generateTime = null;

    /**
     * @var \DateTimeImmutable|null 电子回单文件的过期时间
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '回单过期时间'])]
    #[Assert\Type(type: \DateTimeImmutable::class)]
    private ?\DateTimeImmutable $expireTime = null;

    /**
     * @var string|null 电子回单的文件名称
     */
    #[ORM\Column(length: 255, nullable: true, options: ['comment' => '回单文件名称'])]
    #[Assert\Length(max: 255)]
    #[Assert\Regex(pattern: '/^[a-zA-Z0-9._-]+$/', message: '文件名称包含不合法字符')]
    private ?string $fileName = null;

    /**
     * @var int|null 电子回单文件大小，单位：字节
     */
    #[ORM\Column(nullable: true, options: ['comment' => '回单文件大小'])]
    #[Assert\Positive(message: '文件大小必须为正数')]
    #[Assert\LessThan(value: 104857600, message: '文件大小不能超过100MB')]
    private ?int $fileSize = null;

    /**
     * @var string|null 微信支付返回的原始数据
     */
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '原始响应数据'])]
    #[Assert\Json(message: '原始响应数据必须是有效的JSON格式')]
    private ?string $rawResponse = null;

    /**
     * @var string|null 电子回单申请单号，用于查询申请状态
     */
    #[ORM\Column(length: 64, nullable: true, options: ['comment' => '回单申请单号'])]
    #[Assert\Length(max: 64)]
    #[Assert\Regex(pattern: '/^[a-zA-Z0-9_-]+$/', message: '申请单号只能包含字母、数字、下划线和横线')]
    private ?string $applyNo = null;

    /**
     * @var \DateTimeImmutable|null 电子回单申请时间
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '申请时间'])]
    #[Assert\Type(type: \DateTimeImmutable::class)]
    private ?\DateTimeImmutable $applyTime = null;

    public function __toString(): string
    {
        return (string) $this->getId();
    }

    // Getters and Setters

    public function getTransferBatch(): ?TransferBatch
    {
        return $this->transferBatch;
    }

    public function setTransferBatch(?TransferBatch $transferBatch): void
    {
        $this->transferBatch = $transferBatch;
    }

    public function getTransferDetail(): ?TransferDetail
    {
        return $this->transferDetail;
    }

    public function setTransferDetail(?TransferDetail $transferDetail): void
    {
        $this->transferDetail = $transferDetail;
    }

    public function getOutBatchNo(): ?string
    {
        return $this->outBatchNo;
    }

    public function setOutBatchNo(?string $outBatchNo): void
    {
        $this->outBatchNo = $outBatchNo;
    }

    public function getOutDetailNo(): ?string
    {
        return $this->outDetailNo;
    }

    public function setOutDetailNo(?string $outDetailNo): void
    {
        $this->outDetailNo = $outDetailNo;
    }

    public function getBatchId(): ?string
    {
        return $this->batchId;
    }

    public function setBatchId(?string $batchId): void
    {
        $this->batchId = $batchId;
    }

    public function getDetailId(): ?string
    {
        return $this->detailId;
    }

    public function setDetailId(?string $detailId): void
    {
        $this->detailId = $detailId;
    }

    public function getReceiptType(): ?string
    {
        return $this->receiptType;
    }

    public function setReceiptType(?string $receiptType): void
    {
        $this->receiptType = $receiptType;
    }

    public function getReceiptStatus(): ?TransferReceiptStatus
    {
        return $this->receiptStatus;
    }

    public function setReceiptStatus(?TransferReceiptStatus $receiptStatus): void
    {
        $this->receiptStatus = $receiptStatus;
    }

    public function getDownloadUrl(): ?string
    {
        return $this->downloadUrl;
    }

    public function setDownloadUrl(?string $downloadUrl): void
    {
        $this->downloadUrl = $downloadUrl;
    }

    public function getHashValue(): ?string
    {
        return $this->hashValue;
    }

    public function setHashValue(?string $hashValue): void
    {
        $this->hashValue = $hashValue;
    }

    public function getGenerateTime(): ?\DateTimeImmutable
    {
        return $this->generateTime;
    }

    public function setGenerateTime(?\DateTimeImmutable $generateTime): void
    {
        $this->generateTime = $generateTime;
    }

    public function getExpireTime(): ?\DateTimeImmutable
    {
        return $this->expireTime;
    }

    public function setExpireTime(?\DateTimeImmutable $expireTime): void
    {
        $this->expireTime = $expireTime;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(?string $fileName): void
    {
        $this->fileName = $fileName;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function setFileSize(?int $fileSize): void
    {
        $this->fileSize = $fileSize;
    }

    public function getRawResponse(): ?string
    {
        return $this->rawResponse;
    }

    public function setRawResponse(?string $rawResponse): void
    {
        $this->rawResponse = $rawResponse;
    }

    public function getApplyNo(): ?string
    {
        return $this->applyNo;
    }

    public function setApplyNo(?string $applyNo): void
    {
        $this->applyNo = $applyNo;
    }

    public function getApplyTime(): ?\DateTimeImmutable
    {
        return $this->applyTime;
    }

    public function setApplyTime(?\DateTimeImmutable $applyTime): void
    {
        $this->applyTime = $applyTime;
    }
}