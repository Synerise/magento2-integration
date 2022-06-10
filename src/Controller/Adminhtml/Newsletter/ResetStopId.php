<?php

namespace Synerise\Integration\Controller\Adminhtml\Newsletter;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Psr\Log\LoggerInterface;
use Synerise\Integration\ResourceModel\Cron\Status as StatusResourceModel;

class ResetStopId extends Action implements HttpGetActionInterface
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var StatusResourceModel
     */
    protected $statusResourceModel;

    public function __construct(
        Context $context,
        LoggerInterface $logger,
        StatusResourceModel $statusResourceModel
    ) {
        $this->logger = $logger;
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
        $this->statusResourceModel->resetStopId('subscriber');

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('synerise/dashboard/index');
    }
}
