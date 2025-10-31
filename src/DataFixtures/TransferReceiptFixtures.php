<?php

declare(strict_types=1);

namespace WechatPayTransferBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\When;
use WechatPayTransferBundle\Entity\TransferBatch;
use WechatPayTransferBundle\Entity\TransferDetail;
use WechatPayTransferBundle\Entity\TransferReceipt;
use WechatPayTransferBundle\Enum\TransferBatchStatus;
use WechatPayTransferBundle\Enum\TransferDetailStatus;
use WechatPayTransferBundle\Enum\TransferReceiptStatus;
use WechatPayTransferBundle\Repository\TransferBatchRepository;

/**
 * 转账电子回单数据固件
 *
 * 为转账批次和明细生成相应的电子回单数据，用于测试和开发环境。
 */
#[When(env: 'test')]
#[Autoconfigure(public: true)]
class TransferReceiptFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    private TransferBatchRepository $transferBatchRepository;

    public function __construct(TransferBatchRepository $transferBatchRepository)
    {
        $this->transferBatchRepository = $transferBatchRepository;
    }

    public function load(ObjectManager $manager): void
    {
        // 获取已创建的转账批次
        $batches = $this->transferBatchRepository->findAll();

        foreach ($batches as $batch) {
            // 只为已完成的批次创建回单
            if ($batch->getBatchStatus() === TransferBatchStatus::FINISHED) {
                $this->createReceiptsForBatch($manager, $batch);
            }
        }

        $manager->flush();
    }

    private function createReceiptsForBatch(ObjectManager $manager, TransferBatch $batch): void
    {
        $details = $batch->getDetails();

        foreach ($details as $detail) {
            // 只为转账成功的明细创建回单
            if ($detail->getDetailStatus() === TransferDetailStatus::SUCCESS) {
                $receipt = new TransferReceipt();
                $receipt->setTransferBatch($batch);
                $receipt->setTransferDetail($detail);
                $receipt->setOutBatchNo($batch->getOutBatchNo());
                $receipt->setOutDetailNo($detail->getOutDetailNo());
                $receipt->setBatchId($batch->getBatchId());
                $receipt->setDetailId($detail->getDetailId());

                // 随机生成回单状态
                $statuses = [
                    TransferReceiptStatus::AVAILABLE,
                    TransferReceiptStatus::EXPIRED,
                    TransferReceiptStatus::GENERATING,
                ];
                $randomStatus = $statuses[array_rand($statuses)];
                $receipt->setReceiptStatus($randomStatus);

                // 设置回单申请信息
                if ($randomStatus !== TransferReceiptStatus::GENERATING) {
                    $receipt->setApplyNo('RECEIPT_' . $detail->getOutDetailNo() . '_' . uniqid());
                    $receipt->setApplyTime(new \DateTimeImmutable('-' . random_int(1, 30) . ' days'));
                }

                // 设置回单可用性信息
                if ($randomStatus === TransferReceiptStatus::AVAILABLE) {
                    $receipt->setDownloadUrl('https://images.unsplash.com/photo-' . uniqid() . '?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80');
                    $receipt->setHashValue(md5('receipt_' . $detail->getOutDetailNo()));
                    $receipt->setGenerateTime(new \DateTimeImmutable('-' . random_int(1, 20) . ' days'));
                    $receipt->setExpireTime(new \DateTimeImmutable('+' . random_int(30, 90) . ' days'));
                    $receipt->setFileName('电子回单_' . $detail->getOutDetailNo() . '.pdf');
                    $receipt->setFileSize(random_int(50000, 200000)); // 50KB-200KB
                }

                // 设置原始响应数据
                $rawResponse = json_encode([
                    'out_batch_no' => $batch->getOutBatchNo(),
                    'out_detail_no' => $detail->getOutDetailNo(),
                    'receipt_type' => 'TRANSFER',
                    'receipt_status' => $randomStatus->value,
                    'create_time' => (new \DateTime())->format('Y-m-d\TH:i:s\Z'),
                ]);

                if ($rawResponse === false) {
                    $rawResponse = '{"error": "Failed to encode response data"}';
                }

                $receipt->setRawResponse($rawResponse);

                $manager->persist($receipt);
            }
        }
    }

    public function getDependencies(): array
    {
        return [
            TransferBatchFixtures::class,
            TransferDetailFixtures::class,
        ];
    }

    public static function getGroups(): array
    {
        return ['wechat-pay-transfer', 'receipt'];
    }
}