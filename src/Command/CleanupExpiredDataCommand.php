<?php

declare(strict_types=1);

namespace WechatPayTransferBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use WechatPayTransferBundle\Entity\TransferReceipt;
use WechatPayTransferBundle\Enum\TransferReceiptStatus;
use WechatPayTransferBundle\Repository\TransferReceiptRepository;

/**
 * 清理过期数据命令
 *
 * 清理过期的电子回单数据，释放存储空间并保持数据库整洁。
 * 适用于以下场景：
 * - 定期清理过期数据
 * - 数据库空间优化
 * - 数据生命周期管理
 */
#[WithMonologChannel(channel: 'wechat_pay_transfer')]
#[AsCommand(
    name: 'wechat-pay-transfer:cleanup',
    description: '清理过期的转账和回单数据'
)]
#[Autoconfigure(public: true)]
class CleanupExpiredDataCommand extends Command
{
    private EntityManagerInterface $entityManager;

    /**
     */
    private TransferReceiptRepository $receiptRepository;

    private LoggerInterface $logger;

      /**
     * @param TransferReceiptRepository $receiptRepository
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        TransferReceiptRepository $receiptRepository,
        LoggerInterface $logger,
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->receiptRepository = $receiptRepository;
        $this->logger = $logger;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('清理过期的转账和回单数据')
            ->addOption(
                'days',
                'd',
                InputOption::VALUE_OPTIONAL,
                '保留多少天前的数据',
                30
            )
            ->addOption(
                'receipt-days',
                'r',
                InputOption::VALUE_OPTIONAL,
                '电子回单保留天数',
                90
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                '模拟运行，不实际删除数据'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                '强制执行，不询问确认'
            )
            ->setHelp(<<<'EOF'
                这个命令用于清理过期的转账和回单数据，释放存储空间并保持数据库整洁。

                使用场景：
                - 定期清理过期数据
                - 数据库空间优化
                - 数据生命周期管理

                示例：
                  # 查看将要清理的数据（安全模式）
                  php bin/console wechat-pay-transfer:cleanup --dry-run

                  # 清理30天前的数据
                  php bin/console wechat-pay-transfer:cleanup --days=30

                  # 强制执行不询问确认
                  php bin/console wechat-pay-transfer:cleanup --force
                EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $daysRaw = $input->getOption('days');
        $receiptDaysRaw = $input->getOption('receipt-days');
        $dryRunRaw = $input->getOption('dry-run');
        $forceRaw = $input->getOption('force');

        // 类型断言
        assert(is_numeric($daysRaw));
        assert(is_numeric($receiptDaysRaw));
        assert(is_bool($dryRunRaw));
        assert(is_bool($forceRaw));

        $days = (int) $daysRaw;
        $receiptDays = (int) $receiptDaysRaw;
        $dryRun = $dryRunRaw;
        $force = $forceRaw;

        $output->writeln('<info>开始清理过期数据...</info>');
        $output->writeln("<info>数据保留天数: {$days} 天</info>");
        $output->writeln("<info>回单保留天数: {$receiptDays} 天</info>");

        if ($dryRun) {
            $output->writeln('<comment>模拟运行模式 - 不会实际删除数据</comment>');
        }

        // 计算截止日期
        $cutoffDate = new \DateTime("-{$days} days");
        $receiptCutoffDate = new \DateTime("-{$receiptDays} days");

        $output->writeln("<info>数据截止日期: {$cutoffDate->format('Y-m-d H:i:s')}</info>");
        $output->writeln("<info>回单截止日期: {$receiptCutoffDate->format('Y-m-d H:i:s')}</info>");

        try {
            // 查询将要清理的数据
            $expiredReceipts = $this->findExpiredReceipts($cutoffDate, $receiptCutoffDate);

            if (0 === count($expiredReceipts)) {
                $output->writeln('<info>没有发现需要清理的过期数据</info>');

                return Command::SUCCESS;
            }

            // 显示将要清理的数据统计
            $this->displayCleanupStats($output, $expiredReceipts);

            // 确认操作
            if (!$dryRun && !$force) {
                $helper = $this->getHelper('question');
                assert($helper instanceof QuestionHelper);
                $question = new ConfirmationQuestion(
                    '<question>确定要删除这些过期数据吗？(y/N)</question> ',
                    false
                );

                if (!(bool) $helper->ask($input, $output, $question)) {
                    $output->writeln('<info>操作已取消</info>');

                    return Command::SUCCESS;
                }
            }

            // 执行清理操作
            if ($dryRun) {
                $output->writeln('<comment>模拟运行 - 以下数据将被删除:</comment>');
                $this->dryRunCleanup($output, $expiredReceipts);
            } else {
                $this->performCleanup($output, $expiredReceipts);
            }

            $output->writeln('<info>数据清理完成!</info>');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>数据清理失败: ' . $e->getMessage() . '</error>');
            $this->logger->error('清理过期数据失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * 查找过期的电子回单
     * @return array<TransferReceipt>
     */
    private function findExpiredReceipts(\DateTime $cutoffDate, \DateTime $receiptCutoffDate): array
    {
        // 查找过期的电子回单
        $qb = $this->receiptRepository->createQueryBuilder('r')
            ->where('r.createTime < :cutoffDate')
            ->andWhere('r.receiptStatus IN (:statuses)')
            ->setParameter('cutoffDate', $cutoffDate)
            ->setParameter('statuses', [
                TransferReceiptStatus::EXPIRED,
                TransferReceiptStatus::DOWNLOADED,
                TransferReceiptStatus::FAILED,
            ])
            ->orderBy('r.createTime', 'ASC')
        ;

        /** @var array<TransferReceipt> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 显示清理统计信息
     * @param array<TransferReceipt> $expiredReceipts
     * @return array<string, mixed>
     */
    private function displayCleanupStats(OutputInterface $output, array $expiredReceipts): array
    {
        $stats = [
            'total_receipts' => count($expiredReceipts),
            'by_status' => [],
            'by_age' => [
                'older_than_30_days' => 0,
                'older_than_60_days' => 0,
                'older_than_90_days' => 0,
            ],
        ];

        $now = new \DateTime();

        foreach ($expiredReceipts as $receipt) {
            // 按状态统计
            $receiptStatus = $receipt->getReceiptStatus();
            $status = $receiptStatus->value ?? 'unknown';
            $stats['by_status'][$status] = ($stats['by_status'][$status] ?? 0) + 1;

            // 按年龄统计
            $createTime = $receipt->getCreateTime();
            assert(null !== $createTime);
            $daysOld = $now->diff($createTime)->days;
            if ($daysOld >= 90) {
                ++$stats['by_age']['older_than_90_days'];
            } elseif ($daysOld >= 60) {
                ++$stats['by_age']['older_than_60_days'];
            } elseif ($daysOld >= 30) {
                ++$stats['by_age']['older_than_30_days'];
            }
        }

        $output->writeln('<comment>=== 清理统计 ===</comment>');
        $output->writeln("<info>总过期回单数: {$stats['total_receipts']}</info>");

        $output->writeln('<comment>按状态分布:</comment>');
        foreach ($stats['by_status'] as $status => $count) {
            $output->writeln("  {$status}: {$count}");
        }

        $output->writeln('<comment>按年龄分布:</comment>');
        $output->writeln("  30天以上: {$stats['by_age']['older_than_30_days']}");
        $output->writeln("  60天以上: {$stats['by_age']['older_than_60_days']}");
        $output->writeln("  90天以上: {$stats['by_age']['older_than_90_days']}");

        return $stats;
    }

    /**
     * 模拟运行清理
     * @param array<TransferReceipt> $expiredReceipts
     */
    private function dryRunCleanup(OutputInterface $output, array $expiredReceipts): void
    {
        foreach ($expiredReceipts as $receipt) {
            $createTime = $receipt->getCreateTime();
            $createTimeStr = ($createTime !== null) ? $createTime->format('Y-m-d') : 'N/A';
            $receiptStatus = $receipt->getReceiptStatus();
            $receiptStatusValue = ($receiptStatus !== null) ? $receiptStatus->value : 'N/A';
            $output->writeln("  将删除回单: {$receipt->getApplyNo()} (状态: {$receiptStatusValue}, 创建时间: {$createTimeStr})");
        }
    }

    /**
     * 执行实际清理
     * @param array<TransferReceipt> $expiredReceipts
     */
    private function performCleanup(OutputInterface $output, array $expiredReceipts): void
    {
        $output->writeln('<comment>正在删除过期电子回单...</comment>');
        $deletedCount = 0;

        foreach ($expiredReceipts as $receipt) {
            try {
                $output->writeln("  删除回单: {$receipt->getApplyNo()}");
                $this->entityManager->remove($receipt);
                ++$deletedCount;

                // 每100条数据提交一次，避免内存占用过大
                if (0 === $deletedCount % 100) {
                    $this->entityManager->flush();
                    $output->writeln("  已提交 {$deletedCount} 条记录");
                }
            } catch (\Exception $e) {
                $output->writeln("<error>删除回单失败: {$receipt->getApplyNo()} - {$e->getMessage()}</error>");
                $this->logger->error('删除过期回单失败', [
                    'receipt_id' => $receipt->getId(),
                    'apply_no' => $receipt->getApplyNo(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 提交剩余的删除操作
        $this->entityManager->flush();
        $output->writeln("<info>成功删除 {$deletedCount} 个过期电子回单</info>");
    }
}
