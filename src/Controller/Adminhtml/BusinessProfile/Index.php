<?php

namespace Synerise\Integration\Controller\Adminhtml\BusinessProfile;

class Index extends \Magento\Backend\App\Action
{
    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'Synerise_Integration::business_profile';

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
            ->getConfig()->getTitle()->prepend((__('Business Profiles')));

        return $resultPage;
    }


}