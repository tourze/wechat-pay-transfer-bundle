<?php

declare(strict_types=1);

namespace WechatPayTransferBundle\Tests\Fixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use WechatPayBundle\Entity\Merchant;
use WechatPayTransferBundle\Entity\TransferBatch;
use WechatPayTransferBundle\Entity\TransferDetail;
use WechatPayTransferBundle\Enum\TransferBatchStatus;
use WechatPayTransferBundle\Enum\TransferDetailStatus;

final class TransferTestFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $merchantRepository = $manager->getRepository(Merchant::class);
        $transferBatchRepository = $manager->getRepository(TransferBatch::class);

        if (method_exists($transferBatchRepository, 'count') && $transferBatchRepository->count([]) > 0) {
            return;
        }

        $merchant = new Merchant();
        $merchant->setMchId('fixture-mch');
        $merchant->setApiKey('fixture-api-key');
        $merchant->setCertSerial('fixture-cert-serial');
        $merchant->setValid(true);

        $batch = new TransferBatch();
        $batch->setMerchant($merchant);
        $batch->setOutBatchNo('FIXTURE-BATCH');
        $batch->setBatchName('测试批次');
        $batch->setBatchRemark('测试批次备注');
        $batch->setTotalAmount(1000);
        $batch->setTotalNum(1);
        $batch->setBatchStatus(TransferBatchStatus::PROCESSING);

        $detail = new TransferDetail();
        $detail->setBatch($batch);
        $detail->setOutDetailNo('DETAIL-001');
        $detail->setTransferAmount(1000);
        $detail->setTransferRemark('测试转账');
        $detail->setOpenid('openid-fixture');
        $detail->setDetailStatus(TransferDetailStatus::WAIT_PAY);

        $manager->persist($merchant);
        $manager->persist($batch);
        $manager->persist($detail);
        $manager->flush();
    }
}
