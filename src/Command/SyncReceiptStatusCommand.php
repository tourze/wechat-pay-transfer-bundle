<?php

declare(strict_types=1);

namespace WechatPayTransferBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use WechatPayTransferBundle\Entity\TransferReceipt;
use WechatPayTransferBundle\Enum\TransferReceiptStatus;
use WechatPayTransferBundle\Repository\TransferReceiptRepository;
use WechatPayTransferBundle\Service\TransferReceiptApiService;

/**
 * 电子回单状态同步命令
 * 
 * 定时同步电子回单状态到微信支付服务器，确保回单状态及时更新。
 * 适用于以下场景：
 * - 回单生成状态检查
 * - 异步回单处理监控
 * - 回单下载状态跟踪
 */
#[WithMonologChannel(channel: 'wechat_pay_transfer')]
#[AsCommand(
    name: 'wechat-pay-transfer:sync-receipts',
    description: '同步电子回单状态到微信支付服务器'
)]
#[Autoconfigure(public: true)]
class SyncReceiptStatusCommand extends Command
{
    private EntityManagerInterface $entityManager;

    /**
     */
    private TransferReceiptRepository $receiptRepository;

    private TransferReceiptApiService $receiptApiService;
    private LoggerInterface $logger;

    /**
     * @param TransferReceiptRepository $receiptRepository
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        TransferReceiptRepository $receiptRepository,
        TransferReceiptApiService $receiptApiService,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->receiptRepository = $receiptRepository;
        $this->receiptApiService = $receiptApiService;
        $this->logger = $logger;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('同步电子回单状态到微信支付服务器')
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_OPTIONAL,
                '每次查询的回单数量限制',
                50
            )
            ->addOption(
                'status',
                's',
                InputOption::VALUE_OPTIONAL,
                '指定要同步的状态 (GENERATING)',
                TransferReceiptStatus::GENERATING->value
            )
            ->addOption(
                'batch-id',
                'b',
                InputOption::VALUE_OPTIONAL,
                '指定微信批次号进行同步',
                null
            )
            ->addOption(
                'out-batch-no',
                'o',
                InputOption::VALUE_OPTIONAL,
                '指定商户批次号进行同步',
                null
            )
            ->addOption(
                'force-update',
                'f',
                InputOption::VALUE_NONE,
                '强制更新本地状态，即使状态相同'
            )
            ->addOption(
                'auto-download',
                'a',
                InputOption::VALUE_NONE,
                '自动下载可用的回单文件'
            )
            ->setHelp(<<<'EOF'
这个命令用于同步电子回单状态到微信支付服务器，确保回单状态及时更新。

使用场景：
- 检查正在生成的回单状态
- 自动下载已完成的回单
- 处理生成失败的回单

示例：
  # 同步所有生成中的回单
  php bin/console wechat-pay-transfer:sync-receipts

  # 同步特定批次的回单
  php bin/console wechat-pay-transfer:sync-receipts --out-batch-no=BATCH_001

  # 限制处理数量并自动下载
  php bin/console wechat-pay-transfer:sync-receipts --limit=10 --auto-download
EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limitRaw = $input->getOption('limit');
        assert(is_numeric($limitRaw));
        $limit = (int) $limitRaw;
        $statusFilterRaw = $input->getOption('status');
        $batchIdRaw = $input->getOption('batch-id');
        $outBatchNoRaw = $input->getOption('out-batch-no');
        $forceUpdateRaw = $input->getOption('force-update');
        $autoDownloadRaw = $input->getOption('auto-download');

        // 类型断言
        assert(is_string($statusFilterRaw) || $statusFilterRaw === null);
        assert(is_string($batchIdRaw) || $batchIdRaw === null);
        assert(is_string($outBatchNoRaw) || $outBatchNoRaw === null);
        assert(is_bool($forceUpdateRaw));
        assert(is_bool($autoDownloadRaw));

        $statusFilter = $statusFilterRaw;
        $batchId = $batchIdRaw;
        $outBatchNo = $outBatchNoRaw;
        $forceUpdate = $forceUpdateRaw;
        $autoDownload = $autoDownloadRaw;

        $output->writeln('<info>开始同步电子回单状态...</info>');
        $output->writeln("<info>处理限制: {$limit}</info>");
        $output->writeln("<info>状态过滤: {$statusFilter}</info>");

        if ($batchId !== null) {
            $output->writeln("<info>微信批次号: {$batchId}</info>");
        }

        if ($outBatchNo !== null) {
            $output->writeln("<info>商户批次号: {$outBatchNo}</info>");
        }

        try {
            $syncCount = $this->syncReceiptStatus(
                $output,
                $limit,
                $statusFilter,
                $batchId,
                $outBatchNo,
                $forceUpdate,
                $autoDownload
            );

            $output->writeln('<info>电子回单状态同步完成!</info>');
            $output->writeln("<info>同步数量: {$syncCount}</info>");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $output->writeln('<error>电子回单状态同步失败: ' . $e->getMessage() . '</error>');
            $this->logger->error('电子回单状态同步失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * 同步电子回单状态
     */
    private function syncReceiptStatus(
        OutputInterface $output,
        int $limit,
        ?string $statusFilter,
        ?string $batchId,
        ?string $outBatchNo,
        bool $forceUpdate,
        bool $autoDownload
    ): int {
        $output->writeln('<comment>正在查询电子回单...</comment>');

        $queryBuilder = $this->receiptRepository->createQueryBuilder('r');

        if (!$this->applyStatusFilter($queryBuilder, $statusFilter, $output)) {
            return 0;
        }

        $this->applyBatchFilters($queryBuilder, $batchId, $outBatchNo);

        $queryBuilder->orderBy('r.applyTime', 'ASC')
                     ->setMaxResults($limit);

        /** @var array<TransferReceipt> $receipts */
        $receipts = $queryBuilder->getQuery()->getResult();

        return $this->processReceipts($receipts, $output, $forceUpdate, $autoDownload);
    }

    /**
     * 应用状态过滤条件
     */
    private function applyStatusFilter(QueryBuilder $queryBuilder, ?string $statusFilter, OutputInterface $output): bool
    {
        if ($statusFilter !== null && $statusFilter !== '') {
            try {
                $status = TransferReceiptStatus::from($statusFilter);
                $queryBuilder->where('r.receiptStatus = :status')
                             ->setParameter('status', $status);
            } catch (\ValueError $e) {
                $output->writeln("<error>无效的状态: {$statusFilter}</error>");
                return false;
            }
        } else {
            // 默认同步生成中和生成失败的回单
            $queryBuilder->where('r.receiptStatus IN (:statuses)')
                         ->setParameter('statuses', [
                             TransferReceiptStatus::GENERATING,
                             TransferReceiptStatus::FAILED,
                         ]);
        }
        return true;
    }

    /**
     * 应用批次过滤条件
     */
    private function applyBatchFilters(QueryBuilder $queryBuilder, ?string $batchId, ?string $outBatchNo): void
    {
        if ($outBatchNo !== null) {
            $queryBuilder->andWhere('r.outBatchNo = :outBatchNo')
                         ->setParameter('outBatchNo', $outBatchNo);
        }

        if ($batchId !== null) {
            $queryBuilder->andWhere('r.batchId = :batchId')
                         ->setParameter('batchId', $batchId);
        }
    }

    /**
     * 批量处理回单
     * @param array<TransferReceipt> $receipts
     */
    private function processReceipts(array $receipts, OutputInterface $output, bool $forceUpdate, bool $autoDownload): int
    {
        $syncCount = 0;
        $downloadCount = 0;

        foreach ($receipts as $receipt) {
            assert($receipt instanceof TransferReceipt);
            try {
                $result = $this->processReceipt($receipt, $output, $forceUpdate, $autoDownload);
                if ($result['statusChanged']) {
                    $syncCount++;
                }
                if ($result['downloaded']) {
                    $downloadCount++;
                }
            } catch (\Exception $e) {
                $this->logReceiptError($receipt, $e, $output);
            }

            // 避免API调用过于频繁
            usleep(100000); // 0.1秒
        }

        if ($downloadCount > 0) {
            $output->writeln("<info>自动下载了 {$downloadCount} 个回单文件</info>");
        }

        return $syncCount;
    }

    /**
     * 记录回单处理错误
     */
    private function logReceiptError(TransferReceipt $receipt, \Exception $e, OutputInterface $output): void
    {
        $output->writeln("<error>处理回单失败: {$receipt->getApplyNo()} - {$e->getMessage()}</error>");
        $this->logger->error('处理电子回单失败', [
            'receipt_id' => $receipt->getId(),
            'apply_no' => $receipt->getApplyNo(),
            'error' => $e->getMessage(),
        ]);
    }

    /**
     * 下载电子回单文件
     */
    private function downloadReceipt(OutputInterface $output, TransferReceipt $receipt): void
    {
        $downloadUrl = $receipt->getDownloadUrl();
        if ($downloadUrl === null) {
            $output->writeln("  警告: 回单没有下载URL");
            return;
        }

        try {
            $output->writeln("  正在下载回单文件...");
            $fileContent = $this->receiptApiService->downloadReceipt($downloadUrl);
            
            // 更新状态为已下载
            $receipt->setReceiptStatus(TransferReceiptStatus::DOWNLOADED);
            $this->entityManager->flush();
            
            $output->writeln("  回单文件下载成功，大小: " . strlen($fileContent) . " 字节");
            
            // 这里可以根据需要保存文件到指定位置
            // $this->saveReceiptFile($receipt, $fileContent);
            
        } catch (\Exception $e) {
            $output->writeln("<error>下载回单文件失败: {$e->getMessage()}</error>");
            $this->logger->error('下载电子回单文件失败', [
                'receipt_id' => $receipt->getId(),
                'download_url' => $downloadUrl,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 处理单个回单
     * @return array{statusChanged: bool, downloaded: bool}
     */
    private function processReceipt(
        TransferReceipt $receipt,
        OutputInterface $output,
        bool $forceUpdate,
        bool $autoDownload
    ): array {
        /** @var array{statusChanged: bool, downloaded: bool} $result */
        $result = [
            'statusChanged' => false,
            'downloaded' => false
        ];
        $output->writeln("正在处理回单: {$receipt->getApplyNo()}");

        // 查询回单状态
        $updatedReceipt = $this->queryReceiptStatus($receipt, $output);
        if ($updatedReceipt === null) {
            return $result;
        }

        return $this->processReceiptStatus($receipt, $updatedReceipt, $output, $forceUpdate, $autoDownload);
    }

    /**
     * 处理回单状态变化
     * @return array{statusChanged: bool, downloaded: bool}
     */
    private function processReceiptStatus(
        TransferReceipt $receipt,
        TransferReceipt $updatedReceipt,
        OutputInterface $output,
        bool $forceUpdate,
        bool $autoDownload
    ): array {
        $result = [
            'statusChanged' => false,
            'downloaded' => false
        ];

        $currentStatus = $receipt->getReceiptStatus()?->value;
        $newStatus = $updatedReceipt->getReceiptStatus()?->value;

        if ($this->hasStatusChanged($currentStatus, $newStatus, $forceUpdate)) {
            $this->logStatusChange($currentStatus, $newStatus, $output);
            $result['statusChanged'] = true;

            if ($autoDownload && $newStatus === TransferReceiptStatus::AVAILABLE->value) {
                $this->downloadReceipt($output, $updatedReceipt);
                $result['downloaded'] = true;
            }
        } else {
            $this->logNoStatusChange($currentStatus, $output);
        }

        $this->handleFailedStatus($newStatus, $receipt, $output);

        return $result;
    }

    /**
     * 检查状态是否发生变化
     */
    private function hasStatusChanged(?string $currentStatus, ?string $newStatus, bool $forceUpdate): bool
    {
        return $newStatus !== null && ($currentStatus !== $newStatus || $forceUpdate);
    }

    /**
     * 记录状态变化
     */
    private function logStatusChange(?string $currentStatus, ?string $newStatus, OutputInterface $output): void
    {
        $currentStatusStr = is_string($currentStatus) ? $currentStatus : 'null';
        $output->writeln("  状态变化: {$currentStatusStr} -> {$newStatus}");
    }

    /**
     * 记录无状态变化
     */
    private function logNoStatusChange(?string $currentStatus, OutputInterface $output): void
    {
        $currentStatusStr = is_string($currentStatus) ? $currentStatus : 'null';
        $output->writeln("  状态无变化: {$currentStatusStr}");
    }

    /**
     * 处理失败状态
     */
    private function handleFailedStatus(?string $newStatus, TransferReceipt $receipt, OutputInterface $output): void
    {
        if ($newStatus === TransferReceiptStatus::FAILED->value) {
            $output->writeln("  回单生成失败，建议重新申请");
            $this->handleFailedReceipt($receipt);
        }
    }

    /**
     * 查询回单状态
     */
    private function queryReceiptStatus(TransferReceipt $receipt, OutputInterface $output): ?TransferReceipt
    {
        $outBatchNo = $receipt->getOutBatchNo();
        $batchId = $receipt->getBatchId();

        if ($outBatchNo !== null) {
            $outDetailNo = $receipt->getOutDetailNo();
            return $this->receiptApiService->queryReceiptByOutBatchNo($outBatchNo, $outDetailNo);
        }

        if ($batchId !== null) {
            $detailId = $receipt->getDetailId();
            return $this->receiptApiService->queryReceiptByBatchId($batchId, $detailId);
        }

        $output->writeln("<error>回单缺少必要的标识信息</error>");
        return null;
    }

    /**
     * 处理生成失败的回单
     */
    private function handleFailedReceipt(TransferReceipt $receipt): void
    {
        // 这里可以实现自动重新申请逻辑
        // 或者通知管理员手动处理
        $this->logger->warning('电子回单生成失败', [
            'receipt_id' => $receipt->getId(),
            'apply_no' => $receipt->getApplyNo(),
            'out_batch_no' => $receipt->getOutBatchNo(),
            'batch_id' => $receipt->getBatchId(),
            'raw_response' => $receipt->getRawResponse(),
        ]);
    }
}
