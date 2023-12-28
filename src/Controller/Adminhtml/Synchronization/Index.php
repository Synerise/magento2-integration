<?php
namespace Synerise\Integration\Controller\Adminhtml\Synchronization;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class Index
 */
class Index extends Action implements HttpGetActionInterface
{
    const ADMIN_RESOURCE = 'Synerise_Integration::synchronization';

    const MENU_ID = 'Synerise_Integration::synchronization';

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    )
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();

        $resultPage
            ->getConfig()->getTitle()->prepend((__('Synchronization')));

        return $resultPage;
    }
}
