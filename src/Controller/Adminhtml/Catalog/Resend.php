<?php

namespace Synerise\Integration\Controller\Adminhtml\Catalog;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Cron\Synchronization\Sender\Product as SyncProduct;
use Synerise\Integration\Model\ResourceModel\Cron\Status as StatusResourceModel;


class Resend extends Action implements HttpGetActionInterface
{
    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'Synerise_Integration::synchronization_catalog';

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var StatusResourceModel
     */
    protected $statusResourceModel;

    /**
     * @var SyncProduct
     */
    protected $syncProduct;

    public function __construct(
        Context $context,
        LoggerInterface $logger,
        StatusResourceModel $statusResourceModel,
        SyncProduct $syncProduct
    ) {
        $this->logger = $logger;
        $this->statusResourceModel = $statusResourceModel;
        $this->syncProduct = $syncProduct;

        parent::__construct($context);
    }

    /**
     * Execute action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     * @throws \Magento\Framework\Exception\LocalizedException | \Exception
     */
    public function execute()
    {
        $this->statusResourceModel->resendItems('product');
        $this->syncProduct->markAllAsUnsent();

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('synerise/dashboard/index');
    }
}
