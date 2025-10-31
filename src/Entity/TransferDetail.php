<?php

declare(strict_types=1);

namespace WechatPayTransferBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;
use WechatPayTransferBundle\Enum\TransferDetailStatus;
use WechatPayTransferBundle\Repository\TransferDetailRepository;

#[ORM\Entity(repositoryClass: TransferDetailRepository::class)]
#[ORM\Table(name: 'wechat_payment_transfer_detail', options: ['comment' => '转账明细'])]
class TransferDetail implements \Stringable
{
    use SnowflakeKeyAware;
    use TimestampableAware;
    use BlameableAware;

    #[ORM\ManyToOne(inversedBy: 'details', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?TransferBatch $batch = null;

    #[ORM\Column(length: 32, options: ['comment' => '商家明细单号'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 32)]
    private ?string $outDetailNo = null;

    #[ORM\Column(options: ['comment' => '转账金额'])]
    #[Assert\NotBlank]
    #[Assert\PositiveOrZero]
    private ?int $transferAmount = null;

    #[ORM\Column(length: 32, options: ['comment' => '转账备注'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 32)]
    private ?string $transferRemark = null;

    #[ORM\Column(length: 64, options: ['comment' => '收款用户openid'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    private ?string $openid = null;

    #[ORM\Column(length: 1024, nullable: true, options: ['comment' => '收款用户姓名'])]
    #[Assert\Length(max: 1024)]
    private ?string $userName = null;

    #[ORM\Column(length: 64, nullable: true, options: ['comment' => '微信明细单号'])]
    #[Assert\Length(max: 64)]
    private ?string $detailId = null;

    #[ORM\Column(length: 32, nullable: true, enumType: TransferDetailStatus::class, options: ['comment' => '明细状态'])]
    #[Assert\Choice(callback: [TransferDetailStatus::class, 'cases'])]
    private ?TransferDetailStatus $detailStatus = TransferDetailStatus::INIT;

    public function __construct()
    {
        // 设置默认状态为初始态
        $this->detailStatus = TransferDetailStatus::INIT;
    }

    public function getBatch(): ?TransferBatch
    {
        return $this->batch;
    }

    public function setBatch(?TransferBatch $batch): void
    {
        $this->batch = $batch;
    }

    public function getOutDetailNo(): ?string
    {
        return $this->outDetailNo;
    }

    public function setOutDetailNo(string $outDetailNo): void
    {
        $this->outDetailNo = $outDetailNo;
    }

    public function getTransferAmount(): ?int
    {
        return $this->transferAmount;
    }

    public function setTransferAmount(int $transferAmount): void
    {
        $this->transferAmount = $transferAmount;
    }

    public function getTransferRemark(): ?string
    {
        return $this->transferRemark;
    }

    public function setTransferRemark(string $transferRemark): void
    {
        $this->transferRemark = $transferRemark;
    }

    public function getOpenid(): ?string
    {
        return $this->openid;
    }

    public function setOpenid(string $openid): void
    {
        $this->openid = $openid;
    }

    public function getUserName(): ?string
    {
        return $this->userName;
    }

    public function setUserName(?string $userName): void
    {
        $this->userName = $userName;
    }

    public function getDetailId(): ?string
    {
        return $this->detailId;
    }

    public function setDetailId(?string $detailId): void
    {
        $this->detailId = $detailId;
    }

    public function getDetailStatus(): ?TransferDetailStatus
    {
        return $this->detailStatus;
    }

    public function setDetailStatus(?TransferDetailStatus $detailStatus): void
    {
        $this->detailStatus = $detailStatus;
    }

    public function __toString(): string
    {
        return (string) $this->getId();
    }
}
