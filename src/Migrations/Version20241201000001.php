<?php

declare(strict_types=1);

namespace WechatPayTransferBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create transfer receipt table migration
 * 
 * This migration creates the table for storing electronic receipts
 * generated from WeChat Pay transfer operations.
 * 
 * @see https://pay.weixin.qq.com/doc/v3/merchant/4012716452
 */
final class Version20241201000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create wechat_payment_transfer_receipt table for electronic receipts';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('wechat_payment_transfer_receipt');
        
        // Primary key
        $table->addColumn('id', 'bigint', ['autoincrement' => true]);
        
        // Foreign key relationships
        $table->addColumn('transfer_batch_id', 'bigint', ['notnull' => false]);
        $table->addColumn('transfer_detail_id', 'bigint', ['notnull' => false]);
        
        // Merchant identifiers
        $table->addColumn('out_batch_no', 'string', ['length' => 32, 'notnull' => false]);
        $table->addColumn('out_detail_no', 'string', ['length' => 32, 'notnull' => false]);
        
        // WeChat identifiers
        $table->addColumn('batch_id', 'string', ['length' => 64, 'notnull' => false]);
        $table->addColumn('detail_id', 'string', ['length' => 64, 'notnull' => false]);
        
        // Receipt information
        $table->addColumn('receipt_type', 'string', ['length' => 32, 'notnull' => false]);
        $table->addColumn('receipt_status', 'string', ['length' => 20, 'notnull' => false]);
        $table->addColumn('download_url', 'string', ['length' => 2048, 'notnull' => false]);
        $table->addColumn('hash_value', 'string', ['length' => 128, 'notnull' => false]);
        $table->addColumn('generate_time', 'string', ['length' => 32, 'notnull' => false]);
        $table->addColumn('expire_time', 'string', ['length' => 32, 'notnull' => false]);
        $table->addColumn('file_name', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('file_size', 'bigint', ['notnull' => false]);
        
        // Application tracking
        $table->addColumn('apply_no', 'string', ['length' => 64, 'notnull' => false]);
        $table->addColumn('apply_time', 'datetime', ['notnull' => false]);
        
        // System fields
        $table->addColumn('raw_response', 'text', ['notnull' => false]);
        $table->addColumn('create_time', 'datetime', ['notnull' => false]);
        $table->addColumn('update_time', 'datetime', ['notnull' => false]);
        $table->addColumn('create_user_id', 'bigint', ['notnull' => false]);
        $table->addColumn('update_user_id', 'bigint', ['notnull' => false]);
        
        // Indexes
        $table->setPrimaryKey(['id']);
        $table->addIndex(['transfer_batch_id'], 'idx_receipt_batch');
        $table->addIndex(['transfer_detail_id'], 'idx_receipt_detail');
        $table->addIndex(['out_batch_no'], 'idx_receipt_out_batch_no');
        $table->addIndex(['out_detail_no'], 'idx_receipt_out_detail_no');
        $table->addIndex(['batch_id'], 'idx_receipt_batch_id');
        $table->addIndex(['detail_id'], 'idx_receipt_detail_id');
        $table->addIndex(['receipt_status'], 'idx_receipt_status');
        $table->addIndex(['apply_no'], 'idx_receipt_apply_no');
        $table->addIndex(['apply_time'], 'idx_receipt_apply_time');
        
        // Foreign key constraints
        $table->addForeignKeyConstraint(
            'wechat_payment_transfer_batch',
            ['transfer_batch_id'],
            ['id'],
            ['onDelete' => 'CASCADE'],
            'fk_receipt_batch'
        );
        
        $table->addForeignKeyConstraint(
            'wechat_payment_transfer_detail',
            ['transfer_detail_id'],
            ['id'],
            ['onDelete' => 'CASCADE'],
            'fk_receipt_detail'
        );
        
        // Table comment
        $table->addOption('comment', '转账电子回单');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('wechat_payment_transfer_receipt');
    }
}