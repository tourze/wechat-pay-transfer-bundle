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
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use WechatPayTransferBundle\Entity\TransferDetail;
use WechatPayTransferBundle\Enum\TransferDetailStatus;

/**
 * @extends AbstractCrudController<TransferDetail>
 */
#[AdminCrud(routePath: '/wechat-pay-transfer/transfer-detail', routeName: 'wechat_pay_transfer_transfer_detail')]
final class TransferDetailCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return TransferDetail::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('转账明细')
            ->setEntityLabelInPlural('转账明细')
            ->setPageTitle('index', '转账明细管理')
            ->setPageTitle('new', '新建转账明细')
            ->setPageTitle('edit', '编辑转账明细')
            ->setPageTitle('detail', '转账明细详情')
            ->setDefaultSort(['id' => 'DESC'])
            ->setPaginatorPageSize(20)
            ->setSearchFields(['outDetailNo', 'openid', 'userName', 'detailId'])
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
            ->add('batch')
            ->add('detailStatus')
            ->add('createTime')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->hideOnForm()
        ;

        yield AssociationField::new('batch', '批次')
            ->setRequired(true)
            ->setHelp('选择所属的转账批次')
        ;

        yield TextField::new('outDetailNo', '明细单号')
            ->setRequired(true)
            ->setMaxLength(32)
            ->setHelp('商户系统内部的明细单号，在批次内唯一')
        ;

        yield MoneyField::new('transferAmount', '转账金额')
            ->setRequired(true)
            ->setCurrency('CNY')
            ->setStoredAsCents()
            ->setHelp('转账金额，单位为分')
        ;

        yield TextField::new('transferRemark', '转账备注')
            ->setRequired(true)
            ->setMaxLength(32)
            ->setHelp('转账备注说明，最多32个字符')
        ;

        yield TextField::new('openid', '用户openid')
            ->setRequired(true)
            ->setMaxLength(64)
            ->setHelp('收款用户的微信OpenID')
        ;

        yield TextField::new('userName', '收款用户姓名')
            ->setMaxLength(1024)
            ->setHelp('收款用户的真实姓名，用于核验身份')
        ;

        yield TextField::new('detailId', '微信明细单号')
            ->setMaxLength(64)
            ->setHelp('微信支付系统返回的明细单号')
            ->hideOnForm()
        ;

        yield ChoiceField::new('detailStatus', '明细状态')
            ->setChoices(TransferDetailStatus::getSelectChoices())
            ->setHelp('转账明细的当前状态')
            ->renderAsBadges([
                TransferDetailStatus::INIT->value => 'secondary',
                TransferDetailStatus::WAIT_PAY->value => 'info',
                TransferDetailStatus::PROCESSING->value => 'warning',
                TransferDetailStatus::SUCCESS->value => 'success',
                TransferDetailStatus::FAIL->value => 'danger',
            ])
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
