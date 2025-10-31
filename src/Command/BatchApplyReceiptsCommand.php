<?php

declare(strict_types=1);

namespace WechatPayTransferBundle\Command;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use WechatPayTransferBundle\Entity\TransferBatch;
use WechatPayTransferBundle\Entity\TransferReceipt;
use WechatPayTransferBundle\Repository\TransferBatchRepository;
use WechatPayTransferBundle\Repository\TransferReceiptRepository;
use WechatPayTransferBundle\Service\TransferReceiptApiService;

/**
 * 批量申请电子回单命令
 * 
 * 批量为已完成的转账申请电子回单，确保所有转账都有对应的电子凭证。
 * 适用于以下场景：
 * - 批量申请回单
 * - 自动化回单管理
 * - 财务审计支持
 */
#[WithMonologChannel(channel: 'wechat_pay_transfer')]
#[AsCommand(
    name: 'wechat-pay-transfer:batch-apply-receipts',
    description: '批量申请电子回单'
)]
#[Autoconfigure(public: true)]
class BatchApplyReceiptsCommand extends Command
{
    private TransferBatchRepository $batchRepository;
    private TransferReceiptRepository $receiptRepository;

    private TransferReceiptApiService $receiptApiService;
    private LoggerInterface $logger;

          public function __construct(
        TransferBatchRepository $batchRepository,
        TransferReceiptRepository $receiptRepository,
        TransferReceiptApiService $receiptApiService,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->batchRepository = $batchRepository;
        $this->receiptRepository = $receiptRepository;
        $this->receiptApiService = $receiptApiService;
        $this->logger = $logger;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('批量申请电子回单')
            ->setHelp(
                "这个命令用于批量申请电子回单，确保所有已完成转账都有对应的电子凭证。\n\n" .
                "使用场景：\n" .
                "- 批量申请回单\n" .
                "- 自动化回单管理\n" .
                "- 财务审计支持\n\n" .
                "示例：\n" .
                "# 批量申请所有未申请回单的转账\n" .
                "php bin/console wechat-pay-transfer:batch-apply-receipts"
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>开始批量申请电子回单...</info>');

        try {
            // 查找已完成且未申请回单的转账批次
            $completedBatches = $this->findCompletedBatches();

            if ($completedBatches === []) {
                $output->writeln('<info>没有找到需要申请回单的转账批次</info>');
                return Command::SUCCESS;
            }

            $output->writeln("<info>找到 " . count($completedBatches) . " 个需要申请回单的转账批次</info>");

            $successCount = 0;
            $failCount = 0;

            foreach ($completedBatches as $batch) {
                try {
                    $output->writeln("正在为批次申请回单: {$batch->getOutBatchNo()}");

                    // 批量申请回单
                    $results = $this->receiptApiService->batchApplyReceipts($batch);

                    $output->writeln("  ✓ 批次回单申请成功");
                    assert(isset($results['details']) && is_countable($results['details']));
                    $output->writeln("  ✓ 明细回单申请数量: " . count($results['details']));

                    $successCount++;
                    
                    // 避免API调用过于频繁
                    usleep(200000); // 0.2秒
                    
                } catch (\Exception $e) {
                    $output->writeln("<error>✗ 批次回单申请失败: {$batch->getOutBatchNo()} - {$e->getMessage()}</error>");
                    $this->logger->error('批量申请回单失败', [
                        'batch_id' => $batch->getId(),
                        'out_batch_no' => $batch->getOutBatchNo(),
                        'error' => $e->getMessage(),
                    ]);
                    $failCount++;
                }
            }

            $output->writeln('<info>批量申请完成!</info>');
            $output->writeln("<info>成功: {$successCount} 个批次</info>");
            $output->writeln("<info>失败: {$failCount} 个批次</info>");

            return $failCount === 0 ? Command::SUCCESS : Command::FAILURE;

        } catch (\Exception $e) {
            $output->writeln('<error>批量申请回单失败: ' . $e->getMessage() . '</error>');
            $this->logger->error('批量申请回单失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * 查找已完成且未申请回单的转账批次
     * @return array<TransferBatch>
     */
    private function findCompletedBatches(): array
    {
        // 查找已完成的转账批次
        $qb = $this->batchRepository->createQueryBuilder('b')
            ->where('b.batchStatus = :status')
            ->setParameter('status', 'FINISHED')
            ->orderBy('b.updateTime', 'DESC');

        $completedBatches = $qb->getQuery()->getResult();
        assert(is_array($completedBatches));
        $batchesToProcess = [];

        foreach ($completedBatches as $batch) {
            assert($batch instanceof TransferBatch);
            // 检查是否已经有回单申请记录
            $existingReceipt = $this->receiptRepository->findOneBy([
                'outBatchNo' => $batch->getOutBatchNo(),
                'outDetailNo' => null, // 只检查批次级别的回单
            ]);

            if ($existingReceipt === null) {
                $batchesToProcess[] = $batch;
            }
        }

        return $batchesToProcess;
    }
}
