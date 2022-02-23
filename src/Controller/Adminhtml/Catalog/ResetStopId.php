<?php

namespace Synerise\Integration\Controller\Adminhtml\Catalog;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Cron\Synchronization;

class ResetStopId extends Action implements HttpGetActionInterface
{
    /**
     * @var Synchronization
     */
    protected $synchronization;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    public function __construct(
        Context $context,
        LoggerInterface $logger,
        Synchronization $synchronization
    ) {
        $this->logger = $logger;
        $this->synchronization = $synchronization;

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
        $this->synchronization->resetStopId('product');

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('synerise/dashboard/index');
    }
}
