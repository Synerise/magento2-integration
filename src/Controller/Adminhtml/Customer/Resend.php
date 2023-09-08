<?php

namespace Synerise\Integration\Controller\Adminhtml\Customer;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Synerise\Integration\Model\Synchronization\Customer as SyncCustomer;
use Synerise\Integration\Model\ResourceModel\Cron\Status as StatusResourceModel;

class Resend extends Action implements HttpGetActionInterface
{
    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'Synerise_Integration::synchronization_customer';

    /**
     * @var SyncCustomer
     */
    protected $syncCustomer;

    public function __construct(
        Context $context,
        SyncCustomer $syncCustomer,
        StatusResourceModel $statusResourceModel

    ) {
        $this->syncCustomer = $syncCustomer;
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
        $this->statusResourceModel->resendItems('customer');
        $this->syncCustomer->markAllAsUnsent();

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('synerise/dashboard/index');
    }
}
