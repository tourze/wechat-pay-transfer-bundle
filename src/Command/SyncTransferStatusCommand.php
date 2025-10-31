<?php

declare(strict_types=1);

namespace WechatPayTransferBundle\Command;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use WechatPayTransferBundle\Entity\TransferBatch;
use WechatPayTransferBundle\Entity\TransferDetail;
use WechatPayTransferBundle\Enum\TransferBatchStatus;
use WechatPayTransferBundle\Enum\TransferDetailStatus;
use WechatPayTransferBundle\Repository\TransferBatchRepository;
use WechatPayTransferBundle\Repository\TransferDetailRepository;
use WechatPayTransferBundle\Service\TransferApiService;

/**
 * 转账状态同步命令
 * 
 * 定时同步转账状态到微信支付服务器，确保本地数据与官方数据一致。
 * 适用于以下场景：
 * - 异步通知丢失时的状态补偿
 * - 定期数据一致性检查
 * - 失败重试机制
 */
#[WithMonologChannel(channel: 'wechat_pay_transfer')]
#[AsCommand(
    name: 'wechat-pay-transfer:sync-status',
    description: '同步转账状态到微信支付服务器'
)]
#[Autoconfigure(public: true)]
class SyncTransferStatusCommand extends Command
{
    /**
     */
    private TransferBatchRepository $batchRepository;

    /**
     */
    private TransferDetailRepository $detailRepository;

    private TransferApiService $transferApiService;
    private LoggerInterface $logger;

    /**
     * @param TransferBatchRepository $batchRepository
     * @param TransferDetailRepository $detailRepository
     */
    public function __construct(
        TransferBatchRepository $batchRepository,
        TransferDetailRepository $detailRepository,
        TransferApiService $transferApiService,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->batchRepository = $batchRepository;
        $this->detailRepository = $detailRepository;
        $this->transferApiService = $transferApiService;
        $this->logger = $logger;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('同步转账状态到微信支付服务器')
            ->addOption(
                'batch-limit',
                'l',
                InputOption::VALUE_OPTIONAL,
                '每次查询的批次数量限制',
                50
            )
            ->addOption(
                'detail-limit',
                'd',
                InputOption::VALUE_OPTIONAL,
                '每次查询的明细数量限制',
                100
            )
            ->addOption(
                'status',
                's',
                InputOption::VALUE_OPTIONAL,
                '指定要同步的状态 (PROCESSING, WAIT_PAY)',
                null
            )
            ->addOption(
                'force-update',
                'f',
                InputOption::VALUE_NONE,
                '强制更新本地状态，即使状态相同'
            )
            ->setHelp(<<<'EOF'
这个命令用于同步转账状态到微信支付服务器，确保本地数据与官方数据一致。

使用场景：
- 异步通知丢失时的状态补偿
- 定期数据一致性检查
- 手动状态同步

示例：
  # 同步所有处理中的转账
  php bin/console wechat-pay-transfer:sync-status

  # 只同步特定状态的转账
  php bin/console wechat-pay-transfer:sync-status --status=PROCESSING

  # 限制每次处理的数量
  php bin/console wechat-pay-transfer:sync-status --batch-limit=10
EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $batchLimitRaw = $input->getOption('batch-limit');
        $detailLimitRaw = $input->getOption('detail-limit');
        $statusFilterRaw = $input->getOption('status');
        $forceUpdateRaw = $input->getOption('force-update');

        // 类型断言
        assert(is_numeric($batchLimitRaw));
        assert(is_numeric($detailLimitRaw));
        assert(is_string($statusFilterRaw) || $statusFilterRaw === null);
        assert(is_bool($forceUpdateRaw));

        $batchLimit = (int) $batchLimitRaw;
        $detailLimit = (int) $detailLimitRaw;

        $statusFilter = $statusFilterRaw;
        $forceUpdate = $forceUpdateRaw;

        $output->writeln('<info>开始同步转账状态...</info>');
        $output->writeln("<info>批次处理限制: {$batchLimit}</info>");
        $output->writeln("<info>明细处理限制: {$detailLimit}</info>");

        if ($statusFilter !== null) {
            $output->writeln("<info>状态过滤: {$statusFilter}</info>");
        }

        try {
            // 同步批次状态
            $batchSyncCount = $this->syncBatchStatus($output, $batchLimit, $statusFilter, $forceUpdate);

            // 同步明细状态
            $detailSyncCount = $this->syncDetailStatus($output, $detailLimit, $forceUpdate);

            $output->writeln('<info>状态同步完成!</info>');
            $output->writeln("<info>批次同步数量: {$batchSyncCount}</info>");
            $output->writeln("<info>明细同步数量: {$detailSyncCount}</info>");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $output->writeln('<error>状态同步失败: ' . $e->getMessage() . '</error>');
            $this->logger->error('转账状态同步失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * 同步批次状态
     */
    private function syncBatchStatus(
        OutputInterface $output,
        int $batchLimit,
        ?string $statusFilter,
        bool $forceUpdate
    ): int {
        $output->writeln('<comment>正在同步批次状态...</comment>');

        $queryBuilder = $this->buildBatchQuery();

        if (!$this->applyStatusFilterToQuery($queryBuilder, $statusFilter, $output)) {
            return 0;
        }

        $queryBuilder->orderBy('b.updateTime', 'ASC')
                     ->setMaxResults($batchLimit);

        /** @var array<TransferBatch> $batches */
        $batches = $queryBuilder->getQuery()->getResult();

        return $this->processBatchesSync($batches, $output, $forceUpdate);
    }

    /**
     * 构建基础批次查询
     */
    private function buildBatchQuery(): \Doctrine\ORM\QueryBuilder
    {
        return $this->batchRepository->createQueryBuilder('b');
    }

    /**
     * 应用状态过滤到查询
     */
    private function applyStatusFilterToQuery(
        \Doctrine\ORM\QueryBuilder $queryBuilder,
        ?string $statusFilter,
        OutputInterface $output
    ): bool {
        if ($statusFilter === null) {
            $queryBuilder->where('b.batchStatus IN (:statuses)')
                         ->setParameter('statuses', [
                             TransferBatchStatus::PROCESSING,
                             TransferBatchStatus::FINISHED,
                         ]);
            return true;
        }

        try {
            $status = TransferBatchStatus::from($statusFilter);
            $queryBuilder->where('b.batchStatus = :status')
                         ->setParameter('status', $status);
            return true;
        } catch (\ValueError $e) {
            $output->writeln("<error>无效的状态: {$statusFilter}</error>");
            return false;
        }
    }

    /**
     * 处理批次同步
     * @param array<TransferBatch> $batches
     */
    private function processBatchesSync(
        array $batches,
        OutputInterface $output,
        bool $forceUpdate
    ): int {
        $syncCount = 0;

        foreach ($batches as $batch) {
            assert($batch instanceof TransferBatch);
            $outBatchNo = $batch->getOutBatchNo();

            $result = $this->processBatchSync($batch, $outBatchNo, $output, $forceUpdate);

            if ($result > 0) {
                $syncCount += $result;
            }

            usleep(100000); // 0.1秒
        }

        return $syncCount;
    }

    /**
     * 处理单个批次同步
     */
    private function processBatchSync(
        TransferBatch $batch,
        string $outBatchNo,
        OutputInterface $output,
        bool $forceUpdate
    ): int {
        try {
            $output->writeln("正在同步批次: {$outBatchNo}");

            $result = $this->transferApiService->queryTransferByOutBatchNo(
                $outBatchNo,
                false // 不需要查询明细，节省API调用
            );

            return $this->handleBatchSyncResult($batch, $result, $output, $forceUpdate);

        } catch (\Exception $e) {
            $output->writeln("<error>同步批次失败: {$outBatchNo} - {$e->getMessage()}</error>");
            $this->logger->error('同步批次状态失败', [
                'batch_id' => $batch->getId(),
                'out_batch_no' => $outBatchNo,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * 处理批次同步结果
     * @param array<string, mixed> $result
     */
    private function handleBatchSyncResult(
        TransferBatch $batch,
        array $result,
        OutputInterface $output,
        bool $forceUpdate
    ): int {
        $currentStatus = $batch->getBatchStatus()?->value;
        $newStatus = $result['batch_status'] ?? null;

        // 确保只处理字符串类型的状态值
        if ($newStatus !== null && is_string($newStatus)) {
            if ($this->hasStatusChanged($currentStatus, $newStatus, $forceUpdate)) {
                $this->logBatchStatusChange($currentStatus, $newStatus, $output);
                return 1;
            }
        }

        $this->logBatchNoStatusChange($currentStatus, $output);
        return 0;
    }

    /**
     * 检查状态是否发生变化
     */
    private function hasStatusChanged(
        ?string $currentStatus,
        ?string $newStatus,
        bool $forceUpdate
    ): bool {
        return $newStatus !== null
            && ($currentStatus !== $newStatus || $forceUpdate);
    }

    /**
     * 记录批次状态变化日志
     */
    private function logBatchStatusChange(
        ?string $currentStatus,
        string $newStatus,
        OutputInterface $output
    ): void {
        $currentStatusStr = is_string($currentStatus) ? $currentStatus : 'null';
        $output->writeln("  状态变化: {$currentStatusStr} -> {$newStatus}");
    }

    /**
     * 记录批次无状态变化日志
     */
    private function logBatchNoStatusChange(
        ?string $currentStatus,
        OutputInterface $output
    ): void {
        $currentStatusStr = is_string($currentStatus) ? $currentStatus : 'null';
        $output->writeln("  状态无变化: {$currentStatusStr}");
    }

    /**
     * 同步明细状态
     */
    private function syncDetailStatus(
        OutputInterface $output,
        int $detailLimit,
        bool $forceUpdate
    ): int {
        $output->writeln('<comment>正在同步明细状态...</comment>');

        /** @var array<TransferDetail> $details */
        $details = $this->buildDetailQuery($detailLimit)->getResult();
        $syncCount = 0;

        foreach ($details as $detail) {
            assert($detail instanceof TransferDetail);
            $syncCount += $this->processDetailSync($detail, $output, $forceUpdate);
        }

        return $syncCount;
    }

    /**
     * 构建明细查询
     */
    private function buildDetailQuery(int $detailLimit): \Doctrine\ORM\Query
    {
        return $this->detailRepository->createQueryBuilder('d')
            ->innerJoin('d.batch', 'b')
            ->where('d.detailStatus IN (:statuses)')
            ->setParameter('statuses', [
                TransferDetailStatus::INIT,
                TransferDetailStatus::WAIT_PAY,
                TransferDetailStatus::PROCESSING,
            ])
            ->andWhere('b.batchStatus IN (:batchStatuses)')
            ->setParameter('batchStatuses', [
                TransferBatchStatus::PROCESSING,
                TransferBatchStatus::FINISHED,
            ])
            ->orderBy('d.updateTime', 'ASC')
            ->setMaxResults($detailLimit)
            ->getQuery();
    }

    /**
     * 处理单个明细同步
     */
    private function processDetailSync(
        TransferDetail $detail,
        OutputInterface $output,
        bool $forceUpdate
    ): int {
        $outDetailNo = $detail->getOutDetailNo();
        assert(is_string($outDetailNo));

        try {
            $output->writeln("正在同步明细: {$outDetailNo}");

            $newStatus = $this->determineNewDetailStatus($detail);

            if ($this->shouldUpdateDetailStatus($detail, $newStatus, $forceUpdate)) {
                $this->updateDetailStatus($detail, $newStatus, $output);
                return 1;
            }

            $this->logNoStatusChange($detail, $output);
            return 0;

        } catch (\Exception $e) {
            $this->handleDetailSyncError($detail, $e, $output);
            return 0;
        } finally {
            // 避免API调用过于频繁
            usleep(100000); // 0.1秒
        }
    }

    /**
     * 确定新的明细状态
     */
    private function determineNewDetailStatus(TransferDetail $detail): ?string
    {
        $batch = $detail->getBatch();
        assert($batch instanceof TransferBatch);
        $outBatchNo = $batch->getOutBatchNo();

        // 调用微信支付API查询批次状态（包含明细信息）
        $result = $this->transferApiService->queryTransferByOutBatchNo(
            $outBatchNo,
            true // 查询明细信息
        );

        return $this->extractDetailStatusFromResult($detail, $result);
    }

    /**
     * 从API结果中提取明细状态
     * @param array<string, mixed> $result
     */
    private function extractDetailStatusFromResult(TransferDetail $detail, array $result): ?string
    {
        $outDetailNo = $detail->getOutDetailNo();
        assert(is_string($outDetailNo));

        if (!isset($result['transfer_detail_list']) || !is_array($result['transfer_detail_list'])) {
            return null;
        }

        foreach ($result['transfer_detail_list'] as $detailData) {
            assert(is_array($detailData));
            if (($detailData['out_detail_no'] ?? '') === $outDetailNo) {
                return $detailData['detail_status'] ?? null;
            }
        }

        return null;
    }

    /**
     * 判断是否需要更新明细状态
     */
    private function shouldUpdateDetailStatus(
        TransferDetail $detail,
        ?string $newStatus,
        bool $forceUpdate
    ): bool {
        if ($newStatus === null) {
            return false;
        }

        $currentStatus = $detail->getDetailStatus()?->value;
        return $currentStatus !== $newStatus || $forceUpdate;
    }

    /**
     * 更新明细状态并记录日志
     */
    private function updateDetailStatus(
        TransferDetail $detail,
        ?string $newStatus,
        OutputInterface $output
    ): void {
        $currentStatus = $detail->getDetailStatus()?->value;
        $currentStatusStr = is_string($currentStatus) ? $currentStatus : 'null';
        $output->writeln("  状态变化: {$currentStatusStr} -> {$newStatus}");
    }

    /**
     * 记录状态无变化的日志
     */
    private function logNoStatusChange(TransferDetail $detail, OutputInterface $output): void
    {
        $currentStatus = $detail->getDetailStatus()?->value;
        $currentStatusStr = is_string($currentStatus) ? $currentStatus : 'null';
        $output->writeln("  状态无变化: {$currentStatusStr}");
    }

    /**
     * 处理明细同步错误
     */
    private function handleDetailSyncError(
        TransferDetail $detail,
        \Exception $e,
        OutputInterface $output
    ): void {
        $outDetailNo = $detail->getOutDetailNo();
        assert(is_string($outDetailNo));

        $output->writeln("<error>同步明细失败: {$outDetailNo} - {$e->getMessage()}</error>");
        $this->logger->error('同步明细状态失败', [
            'detail_id' => $detail->getId(),
            'out_detail_no' => $outDetailNo,
            'error' => $e->getMessage(),
        ]);
    }
}
