<?php

namespace Synerise\Integration\Controller\Adminhtml\Synchronization\All;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Customer\Model\ResourceModel\Customer\Collection;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\MessageQueue\Filter;
use Synerise\Integration\MessageQueue\Publisher\Data\Scheduler as Publisher;
use Synerise\Integration\SyneriseApi\Sender\Data\Customer as Sender;

class Select extends Action implements HttpGetActionInterface
{
    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'Synerise_Integration::synchronization';


    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    private $resultPageFactory;

    public function __construct(
        Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;

        parent::__construct($context);
    }


    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('Full Synchronization'));
        return $resultPage;
    }
}
