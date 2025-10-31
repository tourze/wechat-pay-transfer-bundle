<?php

declare(strict_types=1);

namespace WechatPayTransferBundle\DataFixtures;

use Carbon\CarbonImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use WechatPayBundle\Entity\Merchant;
use WechatPayTransferBundle\Entity\TransferBatch;
use WechatPayTransferBundle\Enum\TransferBatchStatus;

#[When(env: 'test')]
class TransferBatchFixtures extends Fixture
{
    public const PROCESSING_BATCH_REFERENCE = 'processing-batch';
    public const FINISHED_BATCH_REFERENCE = 'finished-batch';
    public const CLOSED_BATCH_REFERENCE = 'closed-batch';
    public const TEST_MERCHANT_REFERENCE = 'test-merchant';

    public function load(ObjectManager $manager): void
    {
        // 创建测试商户
        $merchant = new Merchant();
        $merchant->setValid(true);
        $merchant->setMchId('1234567890');
        $merchant->setApiKey('test_api_key_1234567890abcdef1234567890abcdef');
        $merchant->setPemKey('-----BEGIN PRIVATE KEY-----
MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQC7...TEST_KEY...
-----END PRIVATE KEY-----');
        $merchant->setCertSerial('1234567890ABCDEF1234567890ABCDEF12345678');
        $merchant->setPemCert('-----BEGIN CERTIFICATE-----
MIIDpTCCAo2gAwIBAgIUNzAwRG...TEST_CERT...
-----END CERTIFICATE-----');
        $merchant->setRemark('测试商户-转账专用');
        $manager->persist($merchant);
        $this->addReference(self::TEST_MERCHANT_REFERENCE, $merchant);

        $this->createProcessingBatch($manager, $merchant);
        $this->createFinishedBatch($manager, $merchant);
        $this->createClosedBatch($manager, $merchant);
        $this->createLargeBatch($manager, $merchant);

        $manager->flush();
    }

    private function createProcessingBatch(ObjectManager $manager, Merchant $merchant): void
    {
        $batch = new TransferBatch();
        $batch->setMerchant($merchant);
        $batch->setOutBatchNo('TEST_BATCH_' . date('YmdHis') . '_001');
        $batch->setBatchName('工资发放批次');
        $batch->setBatchRemark('2024年1月员工工资');
        $batch->setTotalAmount(500000);
        $batch->setTotalNum(10);
        $batch->setTransferSceneId('1001');
        $batch->setBatchId('1030000071100999991182020315');
        $batch->setBatchStatus(TransferBatchStatus::PROCESSING);
        $batch->setCreateTime(CarbonImmutable::now()->subHours(2));
        $batch->setUpdateTime(CarbonImmutable::now()->subMinutes(30));

        $manager->persist($batch);
        $this->addReference(self::PROCESSING_BATCH_REFERENCE, $batch);
    }

    private function createFinishedBatch(ObjectManager $manager, Merchant $merchant): void
    {
        $batch = new TransferBatch();
        $batch->setMerchant($merchant);
        $batch->setOutBatchNo('TEST_BATCH_' . date('YmdHis') . '_002');
        $batch->setBatchName('奖金发放批次');
        $batch->setBatchRemark('2024年度绩效奖金');
        $batch->setTotalAmount(300000);
        $batch->setTotalNum(5);
        $batch->setTransferSceneId('1002');
        $batch->setBatchId('1030000071100999991182020316');
        $batch->setBatchStatus(TransferBatchStatus::FINISHED);
        $batch->setCreateTime(CarbonImmutable::now()->subDays(1));
        $batch->setUpdateTime(CarbonImmutable::now()->subHours(12));

        $manager->persist($batch);
        $this->addReference(self::FINISHED_BATCH_REFERENCE, $batch);
    }

    private function createClosedBatch(ObjectManager $manager, Merchant $merchant): void
    {
        $batch = new TransferBatch();
        $batch->setMerchant($merchant);
        $batch->setOutBatchNo('TEST_BATCH_' . date('YmdHis') . '_003');
        $batch->setBatchName('退款批次');
        $batch->setBatchRemark('订单退款处理');
        $batch->setTotalAmount(150000);
        $batch->setTotalNum(3);
        $batch->setBatchId('1030000071100999991182020317');
        $batch->setBatchStatus(TransferBatchStatus::CLOSED);
        $batch->setCreateTime(CarbonImmutable::now()->subDays(3));
        $batch->setUpdateTime(CarbonImmutable::now()->subDays(2));

        $manager->persist($batch);
        $this->addReference(self::CLOSED_BATCH_REFERENCE, $batch);
    }

    private function createLargeBatch(ObjectManager $manager, Merchant $merchant): void
    {
        $batch = new TransferBatch();
        $batch->setMerchant($merchant);
        $batch->setOutBatchNo('TEST_BATCH_' . date('YmdHis') . '_004');
        $batch->setBatchName('大额转账批次');
        $batch->setBatchRemark('供应商货款结算');
        $batch->setTotalAmount(10000000);
        $batch->setTotalNum(100);
        $batch->setTransferSceneId('1003');
        $batch->setBatchStatus(TransferBatchStatus::PROCESSING);
        $batch->setCreateTime(CarbonImmutable::now()->subMinutes(45));
        $batch->setUpdateTime(CarbonImmutable::now()->subMinutes(15));

        $manager->persist($batch);
    }
}
