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
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use WechatPayTransferBundle\Entity\TransferBatch;
use WechatPayTransferBundle\Enum\TransferBatchStatus;

/**
 * @extends AbstractCrudController<TransferBatch>
 */
#[AdminCrud(routePath: '/wechat-pay-transfer/transfer-batch', routeName: 'wechat_pay_transfer_transfer_batch')]
final class TransferBatchCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return TransferBatch::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('转账批次')
            ->setEntityLabelInPlural('转账批次')
            ->setPageTitle('index', '转账批次管理')
            ->setPageTitle('new', '新建转账批次')
            ->setPageTitle('edit', '编辑转账批次')
            ->setPageTitle('detail', '转账批次详情')
            ->setDefaultSort(['id' => 'DESC'])
            ->setPaginatorPageSize(20)
            ->setSearchFields(['outBatchNo', 'batchName', 'batchId'])
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
            ->add('merchant')
            ->add('batchStatus')
            ->add('createTime')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->onlyOnDetail()
        ;

        yield AssociationField::new('merchant', '商户')
            ->setRequired(true)
            ->setHelp('选择转账的商户')
        ;

        yield TextField::new('outBatchNo', '商家批次单号')
            ->setRequired(true)
            ->setMaxLength(32)
            ->setHelp('商户系统内部的商家批次单号，只能由数字、大小写字母组成')
        ;

        yield TextField::new('batchName', '批次名称')
            ->setRequired(true)
            ->setMaxLength(32)
            ->setHelp('该笔批量转账的名称')
        ;

        yield TextField::new('batchRemark', '批次备注')
            ->setRequired(true)
            ->setMaxLength(32)
            ->setHelp('转账说明，UTF8编码，最多允许32个字符')
        ;

        yield MoneyField::new('totalAmount', '转账总金额')
            ->setRequired(true)
            ->setCurrency('CNY')
            ->setStoredAsCents()
            ->setHelp('转账金额单位为"分"，必须与批次内所有明细转账金额之和保持一致')
        ;

        yield IntegerField::new('totalNum', '转账总笔数')
            ->setRequired(true)
            ->setHelp('一个转账批次单最多发起一千笔转账，必须与批次内所有明细之和保持一致')
        ;

        yield TextField::new('transferSceneId', '转账场景ID')
            ->setMaxLength(36)
            ->setHelp('转账场景的唯一标识')
            ->hideOnIndex()
        ;

        yield TextField::new('batchId', '微信批次单号')
            ->setMaxLength(64)
            ->setHelp('微信商家转账系统返回的唯一标识')
            ->hideOnForm()
        ;

        yield ChoiceField::new('batchStatus', '批次状态')
            ->setChoices(TransferBatchStatus::getSelectChoices())
            ->setHelp('转账批次的当前状态')
            ->renderAsBadges([
                TransferBatchStatus::PROCESSING->value => 'warning',
                TransferBatchStatus::FINISHED->value => 'success',
                TransferBatchStatus::CLOSED->value => 'danger',
            ])
            ->hideOnForm()
        ;

        yield AssociationField::new('details', '转账明细')
            ->setHelp('该批次下的所有转账明细')
            ->onlyOnDetail()
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
