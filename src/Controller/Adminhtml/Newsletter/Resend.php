<?php

namespace Synerise\Integration\Controller\Adminhtml\Newsletter;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\Helper\Synchronization\Results;
use Synerise\Integration\Helper\Synchronization\Sender\Subscriber;

class Resend extends Action implements HttpGetActionInterface
{
    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'Synerise_Integration::synchronization_subscriber';

    /**
     * @var Synchronization
     */
    private $synchronization;

    /**
     * @var Results
     */
    protected $results;

    public function __construct(
        Context $context,
        Results $results,
        Synchronization $synchronization
    ) {
        $this->results = $results;
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
        $this->synchronization->resetState(Subscriber::MODEL);
        $this->results->truncateTable(Subscriber::MODEL);

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('synerise/dashboard/index');
    }
}
