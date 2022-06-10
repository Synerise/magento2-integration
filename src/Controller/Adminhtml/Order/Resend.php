<?php

namespace Synerise\Integration\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Model\Synchronization\Order as SyncOrder;
use Synerise\Integration\ResourceModel\Cron\Status as StatusResourceModel;


class Resend extends Action implements HttpGetActionInterface
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var SyncOrder
     */
    protected $syncOrder;

    /**
     * @var StatusResourceModel
     */
    protected $statusResourceModel;

    public function __construct(
        Context $context,
        LoggerInterface $logger,
        SyncOrder $syncOrder,
        StatusResourceModel $statusResourceModel
    ) {
        $this->logger = $logger;
        $this->syncOrder = $syncOrder;
        $this->statusResourceModel = $statusResourceModel;

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
        $this->statusResourceModel->resendItems('order');
        $this->syncOrder->markAllAsUnsent();

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('synerise/dashboard/index');
    }
}
