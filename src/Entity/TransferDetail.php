<?php

namespace WechatPayTransferBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Tourze\DoctrineSnowflakeBundle\Service\SnowflakeIdGenerator;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;
use WechatPayTransferBundle\Enum\TransferDetailStatus;
use WechatPayTransferBundle\Repository\TransferDetailRepository;

#[ORM\Entity(repositoryClass: TransferDetailRepository::class)]
#[ORM\Table(name: 'wechat_payment_transfer_detail', options: ['comment' => '转账明细'])]
class TransferDetail implements \Stringable
{
    use TimestampableAware;
    use BlameableAware;
    
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(SnowflakeIdGenerator::class)]
    #[ORM\Column(type: Types::BIGINT, nullable: false, options: ['comment' => 'ID'])]
    private ?string $id = null;

    #[ORM\ManyToOne(inversedBy: 'details')]
    #[ORM\JoinColumn(nullable: false)]
    private ?TransferBatch $batch = null;

    #[ORM\Column(length: 32, options: ['comment' => '商家明细单号'])]
    private ?string $outDetailNo = null;

    #[ORM\Column(options: ['comment' => '转账金额'])]
    private ?int $transferAmount = null;

    #[ORM\Column(length: 32, options: ['comment' => '转账备注'])]
    private ?string $transferRemark = null;

    #[ORM\Column(length: 64, options: ['comment' => '收款用户openid'])]
    private ?string $openid = null;

    #[ORM\Column(length: 1024, nullable: true, options: ['comment' => '收款用户姓名'])]
    private ?string $userName = null;

    #[ORM\Column(length: 64, nullable: true, options: ['comment' => '微信明细单号'])]
    private ?string $detailId = null;

    #[ORM\Column(length: 32, nullable: true, enumType: TransferDetailStatus::class, options: ['comment' => '明细状态'])]
    private ?TransferDetailStatus $detailStatus = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getBatch(): ?TransferBatch
    {
        return $this->batch;
    }

    public function setBatch(?TransferBatch $batch): static
    {
        $this->batch = $batch;

        return $this;
    }

    public function getOutDetailNo(): ?string
    {
        return $this->outDetailNo;
    }

    public function setOutDetailNo(string $outDetailNo): static
    {
        $this->outDetailNo = $outDetailNo;

        return $this;
    }

    public function getTransferAmount(): ?int
    {
        return $this->transferAmount;
    }

    public function setTransferAmount(int $transferAmount): static
    {
        $this->transferAmount = $transferAmount;

        return $this;
    }

    public function getTransferRemark(): ?string
    {
        return $this->transferRemark;
    }

    public function setTransferRemark(string $transferRemark): static
    {
        $this->transferRemark = $transferRemark;

        return $this;
    }

    public function getOpenid(): ?string
    {
        return $this->openid;
    }

    public function setOpenid(string $openid): static
    {
        $this->openid = $openid;

        return $this;
    }

    public function getUserName(): ?string
    {
        return $this->userName;
    }

    public function setUserName(?string $userName): static
    {
        $this->userName = $userName;

        return $this;
    }

    public function getDetailId(): ?string
    {
        return $this->detailId;
    }

    public function setDetailId(?string $detailId): static
    {
        $this->detailId = $detailId;

        return $this;
    }

    public function getDetailStatus(): ?TransferDetailStatus
    {
        return $this->detailStatus;
    }

    public function setDetailStatus(?TransferDetailStatus $detailStatus): static
    {
        $this->detailStatus = $detailStatus;

        return $this;
    }
    public function __toString(): string
    {
        return (string) $this->getId();
    }
}
