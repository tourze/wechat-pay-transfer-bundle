<?php

declare(strict_types=1);

namespace WechatPayTransferBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use WechatPayTransferBundle\Entity\TransferBatch;
use WechatPayTransferBundle\Entity\TransferDetail;
use WechatPayTransferBundle\Enum\TransferDetailStatus;

#[When(env: 'test')]
class TransferDetailFixtures extends Fixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [
            TransferBatchFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $processingBatch = $this->getReference(TransferBatchFixtures::PROCESSING_BATCH_REFERENCE, TransferBatch::class);
        $finishedBatch = $this->getReference(TransferBatchFixtures::FINISHED_BATCH_REFERENCE, TransferBatch::class);
        $closedBatch = $this->getReference(TransferBatchFixtures::CLOSED_BATCH_REFERENCE, TransferBatch::class);

        $this->createProcessingBatchDetails($manager, $processingBatch);
        $this->createFinishedBatchDetails($manager, $finishedBatch);
        $this->createClosedBatchDetails($manager, $closedBatch);

        $manager->flush();
    }

    private function createProcessingBatchDetails(ObjectManager $manager, TransferBatch $batch): void
    {
        $details = [
            [
                'outDetailNo' => 'DETAIL_001_' . date('YmdHis'),
                'amount' => 50000,
                'remark' => '张三工资',
                'openid' => 'openid_zhangsan_123456',
                'userName' => '张三',
                'status' => TransferDetailStatus::SUCCESS,
                'detailId' => '1040000071100999991182020001',
            ],
            [
                'outDetailNo' => 'DETAIL_002_' . date('YmdHis'),
                'amount' => 55000,
                'remark' => '李四工资',
                'openid' => 'openid_lisi_234567',
                'userName' => '李四',
                'status' => TransferDetailStatus::PROCESSING,
                'detailId' => null,
            ],
            [
                'outDetailNo' => 'DETAIL_003_' . date('YmdHis'),
                'amount' => 48000,
                'remark' => '王五工资',
                'openid' => 'openid_wangwu_345678',
                'userName' => '王五',
                'status' => TransferDetailStatus::WAIT_PAY,
                'detailId' => null,
            ],
        ];

        foreach ($details as $detailData) {
            $detail = $this->createDetail($batch, $detailData);
            $manager->persist($detail);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createDetail(TransferBatch $batch, array $data): TransferDetail
    {
        $detail = new TransferDetail();
        $detail->setBatch($batch);

        assert(is_string($data['outDetailNo']));
        $detail->setOutDetailNo($data['outDetailNo']);

        assert(is_int($data['amount']));
        $detail->setTransferAmount($data['amount']);

        assert(is_string($data['remark']));
        $detail->setTransferRemark($data['remark']);

        assert(is_string($data['openid']));
        $detail->setOpenid($data['openid']);

        if (isset($data['userName'])) {
            assert(is_string($data['userName']));
            $detail->setUserName($data['userName']);
        }

        if (isset($data['status'])) {
            assert($data['status'] instanceof TransferDetailStatus);
            $detail->setDetailStatus($data['status']);
        }

        if (array_key_exists('detailId', $data)) {
            $detailId = $data['detailId'];
            if (is_string($detailId)) {
                $detail->setDetailId($detailId);
            } else {
                $detail->setDetailId(null);
            }
        }

        $detail->setCreateTime((new \DateTimeImmutable())->modify('-' . rand(1, 24) . ' hours'));
        $detail->setUpdateTime((new \DateTimeImmutable())->modify('-' . rand(1, 60) . ' minutes'));

        return $detail;
    }

    private function createFinishedBatchDetails(ObjectManager $manager, TransferBatch $batch): void
    {
        $details = [
            [
                'outDetailNo' => 'DETAIL_004_' . date('YmdHis'),
                'amount' => 100000,
                'remark' => '赵六绩效奖金',
                'openid' => 'openid_zhaoliu_456789',
                'userName' => '赵六',
                'status' => TransferDetailStatus::SUCCESS,
                'detailId' => '1040000071100999991182020002',
            ],
            [
                'outDetailNo' => 'DETAIL_005_' . date('YmdHis'),
                'amount' => 120000,
                'remark' => '钱七绩效奖金',
                'openid' => 'openid_qianqi_567890',
                'userName' => '钱七',
                'status' => TransferDetailStatus::SUCCESS,
                'detailId' => '1040000071100999991182020003',
            ],
            [
                'outDetailNo' => 'DETAIL_006_' . date('YmdHis'),
                'amount' => 80000,
                'remark' => '孙八绩效奖金',
                'openid' => 'openid_sunba_678901',
                'status' => TransferDetailStatus::SUCCESS,
                'detailId' => '1040000071100999991182020004',
            ],
        ];

        foreach ($details as $detailData) {
            $detail = $this->createDetail($batch, $detailData);
            $manager->persist($detail);
        }
    }

    private function createClosedBatchDetails(ObjectManager $manager, TransferBatch $batch): void
    {
        $details = [
            [
                'outDetailNo' => 'DETAIL_007_' . date('YmdHis'),
                'amount' => 50000,
                'remark' => '订单退款001',
                'openid' => 'openid_customer_789012',
                'status' => TransferDetailStatus::FAIL,
                'detailId' => null,
            ],
            [
                'outDetailNo' => 'DETAIL_008_' . date('YmdHis'),
                'amount' => 75000,
                'remark' => '订单退款002',
                'openid' => 'openid_customer_890123',
                'status' => TransferDetailStatus::FAIL,
                'detailId' => null,
            ],
            [
                'outDetailNo' => 'DETAIL_009_' . date('YmdHis'),
                'amount' => 25000,
                'remark' => '订单退款003',
                'openid' => 'openid_customer_901234',
                'status' => TransferDetailStatus::FAIL,
                'detailId' => null,
            ],
        ];

        foreach ($details as $detailData) {
            $detail = $this->createDetail($batch, $detailData);
            $manager->persist($detail);
        }
    }
}
