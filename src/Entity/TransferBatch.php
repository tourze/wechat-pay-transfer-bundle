<?php

declare(strict_types=1);

namespace WechatPayTransferBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;
use WechatPayBundle\Entity\Merchant;
use WechatPayTransferBundle\Enum\TransferBatchStatus;
use WechatPayTransferBundle\Repository\TransferBatchRepository;

#[ORM\Entity(repositoryClass: TransferBatchRepository::class)]
#[ORM\Table(name: 'wechat_payment_transfer_batch', options: ['comment' => '商家转账'])]
class TransferBatch implements \Stringable
{
    use SnowflakeKeyAware;
    use TimestampableAware;
    use BlameableAware;

    #[ORM\ManyToOne(cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false, options: ['comment' => '商户'])]
    private ?Merchant $merchant = null;

    /**
     * @var string 商户系统内部的商家批次单号，要求此参数只能由数字、大小写字母组成，在商户系统内部唯一
     */
    #[ORM\Column(length: 32, options: ['comment' => '商家批次单号'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 32)]
    private string $outBatchNo;

    /**
     * @var string 该笔批量转账的名称
     */
    #[ORM\Column(length: 32, options: ['comment' => '批次名称'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 32)]
    private string $batchName;

    /**
     * @var string 转账说明，UTF8编码，最多允许32个字符
     */
    #[ORM\Column(length: 32, options: ['comment' => '批次备注'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 32)]
    private string $batchRemark;

    /**
     * @var int 转账金额单位为“分”。转账总金额必须与批次内所有明细转账金额之和保持一致，否则无法发起转账操作
     */
    #[ORM\Column(options: ['comment' => '转账总金额'])]
    #[Assert\NotBlank]
    #[Assert\PositiveOrZero]
    private int $totalAmount;

    /**
     * @var int 一个转账批次单最多发起一千笔转账。转账总笔数必须与批次内所有明细之和保持一致，否则无法发起转账操作
     */
    #[ORM\Column(options: ['comment' => '转账总笔数'])]
    #[Assert\NotBlank]
    #[Assert\PositiveOrZero]
    private int $totalNum;

    #[ORM\Column(length: 64, nullable: true, options: ['comment' => '应用ID'])]
    #[Assert\Length(max: 64)]
    private ?string $appId = null;

    #[ORM\Column(length: 36, nullable: true, options: ['comment' => '转账场景 ID'])]
    #[Assert\Length(max: 36)]
    private ?string $transferSceneId = null;

    /**
     * @var string|null 微信批次单号，微信商家转账系统返回的唯一标识
     */
    #[ORM\Column(length: 64, nullable: true, options: ['comment' => '微信批次单号'])]
    #[Assert\Length(max: 64)]
    private ?string $batchId = null;

    /**
     * @var Collection<int, TransferDetail>
     */
    #[ORM\OneToMany(targetEntity: TransferDetail::class, mappedBy: 'batch')]
    private Collection $details;

    #[ORM\Column(length: 30, nullable: true, enumType: TransferBatchStatus::class, options: ['comment' => '批次状态'])]
    #[Assert\Choice(callback: [TransferBatchStatus::class, 'cases'])]
    private ?TransferBatchStatus $batchStatus = null;

    public function __construct()
    {
        $this->details = new ArrayCollection();
        $this->outBatchNo = '';
        $this->batchName = '';
        $this->batchRemark = '';
        $this->totalAmount = 0;
        $this->totalNum = 0;
    }

    public function getMerchant(): ?Merchant
    {
        return $this->merchant;
    }

    public function setMerchant(?Merchant $merchant): void
    {
        $this->merchant = $merchant;
    }

    public function getOutBatchNo(): string
    {
        return $this->outBatchNo;
    }

    public function setOutBatchNo(string $outBatchNo): void
    {
        $this->outBatchNo = $outBatchNo;
    }

    public function getBatchName(): string
    {
        return $this->batchName;
    }

    public function setBatchName(string $batchName): void
    {
        $this->batchName = $batchName;
    }

    public function getBatchRemark(): string
    {
        return $this->batchRemark;
    }

    public function setBatchRemark(string $batchRemark): void
    {
        $this->batchRemark = $batchRemark;
    }

    public function getTotalAmount(): int
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(int $totalAmount): void
    {
        $this->totalAmount = $totalAmount;
    }

    public function getTotalNum(): int
    {
        return $this->totalNum;
    }

    public function setTotalNum(int $totalNum): void
    {
        $this->totalNum = $totalNum;
    }

    public function getAppId(): ?string
    {
        return $this->appId;
    }

    public function setAppId(?string $appId): void
    {
        $this->appId = $appId;
    }

    public function getTransferSceneId(): ?string
    {
        return $this->transferSceneId;
    }

    public function setTransferSceneId(?string $transferSceneId): void
    {
        $this->transferSceneId = $transferSceneId;
    }

    public function getBatchId(): ?string
    {
        return $this->batchId;
    }

    public function setBatchId(?string $batchId): void
    {
        $this->batchId = $batchId;
    }

    /**
     * @return Collection<int, TransferDetail>
     */
    public function getDetails(): Collection
    {
        return $this->details;
    }

    public function addDetail(TransferDetail $detail): void
    {
        if (!$this->details->contains($detail)) {
            $this->details->add($detail);
            $detail->setBatch($this);
        }
    }

    public function removeDetail(TransferDetail $detail): void
    {
        if ($this->details->removeElement($detail)) {
            // set the owning side to null (unless already changed)
            if ($detail->getBatch() === $this) {
                $detail->setBatch(null);
            }
        }
    }

    public function getBatchStatus(): ?TransferBatchStatus
    {
        return $this->batchStatus;
    }

    public function setBatchStatus(?TransferBatchStatus $batchStatus): void
    {
        $this->batchStatus = $batchStatus;
    }

    public function __toString(): string
    {
        return (string) $this->getId();
    }
}
