<?php

declare(strict_types=1);

namespace WechatPayTransferBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use WechatPayTransferBundle\Entity\TransferReceipt;
use WechatPayTransferBundle\Enum\TransferReceiptStatus;

/**
 * 转账电子回单管理控制器
 * 
 * 提供转账电子回单的管理界面，包括查看、搜索、筛选等功能。
 * 
 * @see https://pay.weixin.qq.com/doc/v3/merchant/4012716452
 */
#[AdminCrud(routePath: '/wechat-pay-transfer/transfer-receipt', routeName: 'wechat_pay_transfer_transfer_receipt')]
final class TransferReceiptCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return TransferReceipt::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('电子回单')
            ->setEntityLabelInPlural('电子回单')
            ->setPageTitle('index', '电子回单管理')
            ->setPageTitle('new', '新建电子回单')
            ->setPageTitle('edit', '编辑电子回单')
            ->setPageTitle('detail', '电子回单详情')
            ->setDefaultSort(['id' => 'DESC'])
            ->setPaginatorPageSize(20)
            ->setSearchFields(['outBatchNo', 'outDetailNo', 'batchId', 'detailId', 'applyNo', 'fileName'])
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->setPermission(Action::NEW, 'ROLE_ADMIN')
            ->setPermission(Action::EDIT, 'ROLE_ADMIN')
            ->setPermission(Action::DELETE, 'ROLE_ADMIN')
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('transferBatch')
            ->add('transferDetail')
            ->add('receiptStatus')
            ->add('receiptType')
            ->add('applyTime')
            ->add('generateTime')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->onlyOnDetail()
        ;

        yield AssociationField::new('transferBatch', '转账批次')
            ->setRequired(false)
            ->setHelp('关联的转账批次')
            ->hideOnForm()
        ;

        yield AssociationField::new('transferDetail', '转账明细')
            ->setRequired(false)
            ->setHelp('关联的转账明细')
            ->hideOnForm()
        ;

        yield TextField::new('outBatchNo', '商户批次单号')
            ->setMaxLength(32)
            ->setHelp('商户系统内部的批次单号')
        ;

        yield TextField::new('outDetailNo', '商户明细单号')
            ->setMaxLength(32)
            ->setHelp('商户系统内部的明细单号')
            ->hideOnIndex()
        ;

        yield TextField::new('batchId', '微信批次单号')
            ->setMaxLength(64)
            ->setHelp('微信支付系统返回的批次单号')
        ;

        yield TextField::new('detailId', '微信明细单号')
            ->setMaxLength(64)
            ->setHelp('微信支付系统返回的明细单号')
            ->hideOnIndex()
        ;

        yield TextField::new('receiptType', '回单类型')
            ->setMaxLength(32)
            ->setHelp('电子回单类型，如：TRANSACTION_DETAIL')
            ->hideOnIndex()
        ;

        yield ChoiceField::new('receiptStatus', '回单状态')
            ->setFormTypeOptions([
                'choices' => TransferReceiptStatus::getSelectChoices(),
                'choice_label' => function ($choice) {
                    return $choice instanceof TransferReceiptStatus ? $choice->getLabel() : $choice;
                },
                'choice_value' => function ($choice) {
                    return $choice instanceof TransferReceiptStatus ? $choice->value : $choice;
                },
            ])
            ->setHelp('电子回单的当前状态')
            ->renderAsBadges([
                TransferReceiptStatus::GENERATING->value => 'warning',
                TransferReceiptStatus::AVAILABLE->value => 'success',
                TransferReceiptStatus::EXPIRED->value => 'secondary',
                TransferReceiptStatus::FAILED->value => 'danger',
                TransferReceiptStatus::DOWNLOADED->value => 'info',
            ])
        ;

        yield UrlField::new('downloadUrl', '下载地址')
            ->setHelp('电子回单文件的下载URL')
            ->hideOnForm()
            ->onlyOnDetail()
        ;

        yield TextField::new('hashValue', '文件哈希')
            ->setMaxLength(128)
            ->setHelp('回单文件的哈希值，用于校验文件完整性')
            ->hideOnIndex()
            ->hideOnForm()
        ;

        yield DateTimeField::new('generateTime', '生成时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->setHelp('电子回单文件的生成时间')
            ->hideOnForm()
        ;

        yield DateTimeField::new('expireTime', '过期时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->setHelp('电子回单文件的过期时间')
            ->hideOnForm()
        ;

        yield TextField::new('fileName', '文件名称')
            ->setMaxLength(255)
            ->setHelp('电子回单的文件名称')
            ->hideOnForm()
        ;

        yield IntegerField::new('fileSize', '文件大小')
            ->setHelp('电子回单文件大小，单位：字节')
            ->hideOnForm()
        ;

        yield TextField::new('applyNo', '申请单号')
            ->setMaxLength(64)
            ->setHelp('电子回单申请单号，用于查询申请状态')
            ->hideOnForm()
        ;

        yield DateTimeField::new('applyTime', '申请时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->hideOnForm()
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->onlyOnDetail()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->onlyOnDetail()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;
    }
}