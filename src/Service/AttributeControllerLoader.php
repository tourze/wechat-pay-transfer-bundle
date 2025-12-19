<?php

declare(strict_types=1);

namespace WechatPayTransferBundle\Service;

use Symfony\Bundle\FrameworkBundle\Routing\AttributeRouteControllerLoader;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Routing\RouteCollection;
use WechatPayTransferBundle\Controller\Api\Receipt\ApplyReceiptByOutBatchNoController;
use WechatPayTransferBundle\Controller\Api\Receipt\ApplyReceiptByBatchIdController;
use WechatPayTransferBundle\Controller\Api\Receipt\QueryReceiptByOutBatchNoController;
use WechatPayTransferBundle\Controller\Api\Receipt\QueryReceiptByBatchIdController;
use WechatPayTransferBundle\Controller\Api\Receipt\DownloadReceiptController;
use WechatPayTransferBundle\Controller\Api\Receipt\BatchApplyReceiptsController;
use WechatPayTransferBundle\Controller\Api\Receipt\ReceiptIndexController;
use WechatPayTransferBundle\Controller\Api\Transfer\CancelTransferController;
use WechatPayTransferBundle\Controller\Api\Transfer\GenerateAppConfirmParametersController;
use WechatPayTransferBundle\Controller\Api\Transfer\GenerateJsApiConfirmParametersController;
use WechatPayTransferBundle\Controller\Api\Transfer\HandleTransferNotificationController;
use WechatPayTransferBundle\Controller\Api\Transfer\InitiateTransferController;
use WechatPayTransferBundle\Controller\Api\Transfer\QueryTransferController;
use WechatPayTransferBundle\Controller\Api\Transfer\SetupTransferNotificationController;
use WechatPayTransferBundle\Controller\Api\Transfer\TransferIndexController;
use Tourze\RoutingAutoLoaderBundle\Service\RoutingAutoLoaderInterface;

#[AutoconfigureTag(name: 'routing.loader')]
class AttributeControllerLoader extends Loader implements RoutingAutoLoaderInterface
{
    private AttributeRouteControllerLoader $controllerLoader;

    private RouteCollection $collection;

    public function __construct()
    {
        parent::__construct();
        $this->controllerLoader = new AttributeRouteControllerLoader();

        $this->collection = new RouteCollection();
        // Receipt controllers
        $this->collection->addCollection($this->controllerLoader->load(ApplyReceiptByOutBatchNoController::class));
        $this->collection->addCollection($this->controllerLoader->load(ApplyReceiptByBatchIdController::class));
        $this->collection->addCollection($this->controllerLoader->load(QueryReceiptByOutBatchNoController::class));
        $this->collection->addCollection($this->controllerLoader->load(QueryReceiptByBatchIdController::class));
        $this->collection->addCollection($this->controllerLoader->load(DownloadReceiptController::class));
        $this->collection->addCollection($this->controllerLoader->load(BatchApplyReceiptsController::class));
        $this->collection->addCollection($this->controllerLoader->load(ReceiptIndexController::class));
        // Transfer controllers
        $this->collection->addCollection($this->controllerLoader->load(CancelTransferController::class));
        $this->collection->addCollection($this->controllerLoader->load(GenerateAppConfirmParametersController::class));
        $this->collection->addCollection($this->controllerLoader->load(GenerateJsApiConfirmParametersController::class));
        $this->collection->addCollection($this->controllerLoader->load(HandleTransferNotificationController::class));
        $this->collection->addCollection($this->controllerLoader->load(InitiateTransferController::class));
        $this->collection->addCollection($this->controllerLoader->load(QueryTransferController::class));
        $this->collection->addCollection($this->controllerLoader->load(SetupTransferNotificationController::class));
        $this->collection->addCollection($this->controllerLoader->load(TransferIndexController::class));
    }

    public function load(mixed $resource, ?string $type = null): RouteCollection
    {
        return $this->collection;
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        return false;
    }

    public function autoload(): RouteCollection
    {
        return $this->collection;
    }
}