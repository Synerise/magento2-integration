<?php

namespace Synerise\Integration\Controller\Adminhtml\BusinessProfile;

class Index extends \Magento\Backend\App\Action
{
    protected $resultPageFactory = false;

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
            ->setActiveMenu('Synerise_Integration::synerise_businessprofile')
            ->getConfig()->getTitle()->prepend((__('Business Profiles')));

        return $resultPage;
    }


}