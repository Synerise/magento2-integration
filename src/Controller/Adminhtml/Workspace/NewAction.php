<?php

namespace Synerise\Integration\Controller\Adminhtml\Workspace;

use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;

class NewAction extends Action
{
    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'Synerise_Integration::workspace_add';

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Action\Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Action\Context $context,
        PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;

        parent::__construct($context);
    }

    /**
     * Edit Workspace
     *
     * @return \Magento\Framework\Controller\ResultInterface
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->getConfig()->getTitle()->prepend(__('Add Workspace'));
        return $resultPage;
    }
}