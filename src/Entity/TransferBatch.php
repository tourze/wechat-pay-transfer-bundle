<?php

namespace WechatPayTransferBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Service\SnowflakeIdGenerator;
use Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn;
use Tourze\DoctrineTimestampBundle\Attribute\UpdateTimeColumn;
use Tourze\DoctrineUserBundle\Attribute\CreatedByColumn;
use Tourze\DoctrineUserBundle\Attribute\UpdatedByColumn;
use Tourze\EasyAdmin\Attribute\Column\ExportColumn;
use Tourze\EasyAdmin\Attribute\Column\ListColumn;
use Tourze\EasyAdmin\Attribute\Filter\Filterable;
use WechatPayBundle\Entity\Merchant;
use WechatPayBundle\Enum\TransferBatchStatus;
use WechatPayTransferBundle\Repository\TransferBatchRepository;

#[ORM\Entity(repositoryClass: TransferBatchRepository::class)]
#[ORM\Table(name: 'wechat_payment_transfer_batch', options: ['comment' => '商家转账'])]
class TransferBatch
{
    #[ExportColumn]
    #[ListColumn(order: -1, sorter: true)]
    #[Groups(['restful_read', 'admin_curd', 'recursive_view', 'api_tree'])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(SnowflakeIdGenerator::class)]
    #[ORM\Column(type: Types::BIGINT, nullable: false, options: ['comment' => 'ID'])]
    private ?string $id = '0';

    #[CreatedByColumn]
    #[Groups(['restful_read'])]
    #[ORM\Column(nullable: true, options: ['comment' => '创建人'])]
    private ?string $createdBy = null;

    #[UpdatedByColumn]
    #[Groups(['restful_read'])]
    #[ORM\Column(nullable: true, options: ['comment' => '更新人'])]
    private ?string $updatedBy = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, options: ['comment' => '商户'])]
    private ?Merchant $merchant = null;

    /**
     * @var string 商户系统内部的商家批次单号，要求此参数只能由数字、大小写字母组成，在商户系统内部唯一
     */
    #[ORM\Column(length: 32, options: ['comment' => '商家批次单号'])]
    private string $outBatchNo;

    /**
     * @var string 该笔批量转账的名称
     */
    #[ORM\Column(length: 32, options: ['comment' => '批次名称'])]
    private string $batchName;

    /**
     * @var string 转账说明，UTF8编码，最多允许32个字符
     */
    #[ORM\Column(length: 32, options: ['comment' => '批次备注'])]
    private string $batchRemark;

    /**
     * @var int 转账金额单位为“分”。转账总金额必须与批次内所有明细转账金额之和保持一致，否则无法发起转账操作
     */
    #[ORM\Column(options: ['comment' => '转账总金额'])]
    private int $totalAmount;

    /**
     * @var int 一个转账批次单最多发起一千笔转账。转账总笔数必须与批次内所有明细之和保持一致，否则无法发起转账操作
     */
    #[ORM\Column(options: ['comment' => '转账总笔数'])]
    private int $totalNum;

    #[ORM\Column(length: 36, nullable: true)]
    private ?string $transferSceneId = null;

    /**
     * @var string|null 微信批次单号，微信商家转账系统返回的唯一标识
     */
    #[ORM\Column(length: 64, nullable: true, options: ['comment' => '微信批次单号'])]
    private ?string $batchId = null;

    #[ORM\OneToMany(mappedBy: 'batch', targetEntity: TransferDetail::class)]
    private Collection $details;

    #[ORM\Column(length: 30, nullable: true, enumType: TransferBatchStatus::class, options: ['comment' => '批次状态'])]
    private ?TransferBatchStatus $batchStatus = null;

    #[Filterable]
    #[IndexColumn]
    #[ListColumn(order: 98, sorter: true)]
    #[ExportColumn]
    #[CreateTimeColumn]
    #[Groups(['restful_read', 'admin_curd', 'restful_read'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '创建时间'])]
    private ?\DateTimeInterface $createTime = null;

    #[UpdateTimeColumn]
    #[ListColumn(order: 99, sorter: true)]
    #[Groups(['restful_read', 'admin_curd', 'restful_read'])]
    #[Filterable]
    #[ExportColumn]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '更新时间'])]
    private ?\DateTimeInterface $updateTime = null;

    public function __construct()
    {
        $this->details = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setCreatedBy(?string $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getCreatedBy(): ?string
    {
        return $this->createdBy;
    }

    public function setUpdatedBy(?string $updatedBy): self
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    public function getUpdatedBy(): ?string
    {
        return $this->updatedBy;
    }

    public function getMerchant(): ?Merchant
    {
        return $this->merchant;
    }

    public function setMerchant(?Merchant $merchant): static
    {
        $this->merchant = $merchant;

        return $this;
    }

    public function getOutBatchNo(): string
    {
        return $this->outBatchNo;
    }

    public function setOutBatchNo(string $outBatchNo): static
    {
        $this->outBatchNo = $outBatchNo;

        return $this;
    }

    public function getBatchName(): string
    {
        return $this->batchName;
    }

    public function setBatchName(string $batchName): static
    {
        $this->batchName = $batchName;

        return $this;
    }

    public function getBatchRemark(): string
    {
        return $this->batchRemark;
    }

    public function setBatchRemark(string $batchRemark): static
    {
        $this->batchRemark = $batchRemark;

        return $this;
    }

    public function getTotalAmount(): int
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(int $totalAmount): static
    {
        $this->totalAmount = $totalAmount;

        return $this;
    }

    public function getTotalNum(): int
    {
        return $this->totalNum;
    }

    public function setTotalNum(int $totalNum): static
    {
        $this->totalNum = $totalNum;

        return $this;
    }

    public function getTransferSceneId(): ?string
    {
        return $this->transferSceneId;
    }

    public function setTransferSceneId(?string $transferSceneId): static
    {
        $this->transferSceneId = $transferSceneId;

        return $this;
    }

    public function getBatchId(): ?string
    {
        return $this->batchId;
    }

    public function setBatchId(?string $batchId): static
    {
        $this->batchId = $batchId;

        return $this;
    }

    /**
     * @return Collection<int, TransferDetail>
     */
    public function getDetails(): Collection
    {
        return $this->details;
    }

    public function addDetail(TransferDetail $detail): static
    {
        if (!$this->details->contains($detail)) {
            $this->details->add($detail);
            $detail->setBatch($this);
        }

        return $this;
    }

    public function removeDetail(TransferDetail $detail): static
    {
        if ($this->details->removeElement($detail)) {
            // set the owning side to null (unless already changed)
            if ($detail->getBatch() === $this) {
                $detail->setBatch(null);
            }
        }

        return $this;
    }

    public function getBatchStatus(): ?TransferBatchStatus
    {
        return $this->batchStatus;
    }

    public function setBatchStatus(?TransferBatchStatus $batchStatus): static
    {
        $this->batchStatus = $batchStatus;

        return $this;
    }

    public function setCreateTime(?\DateTimeInterface $createdAt): void
    {
        $this->createTime = $createdAt;
    }

    public function getCreateTime(): ?\DateTimeInterface
    {
        return $this->createTime;
    }

    public function setUpdateTime(?\DateTimeInterface $updateTime): void
    {
        $this->updateTime = $updateTime;
    }

    public function getUpdateTime(): ?\DateTimeInterface
    {
        return $this->updateTime;
    }
}
