<?php

namespace Synerise\Integration\Controller\Adminhtml\Catalog;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Synerise\Integration\Helper\Queue;
use Synerise\Integration\Model\MessageQueue\Data\Range\Publisher;
use Synerise\Integration\Model\Synchronization\Product;


class FullSchedule extends Action implements HttpGetActionInterface
{
    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'Synerise_Integration::synchronization_catalog';

    /**
     * @var Product
     */
    protected $product;

    /**
     * @var Publisher
     */
    private $publisher;

    /**
     * @var Queue
     */
    private $queueHelper;

    public function __construct(
        Context $context,
        Publisher $publisher,
        Product $product,
        Queue $queueHelper
    ) {
        $this->publisher = $publisher;
        $this->product = $product;
        $this->queueHelper = $queueHelper;

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
        $enabledStoreIds = $this->queueHelper->getEnabledStores();
        foreach($enabledStoreIds as $enabledStoreId) {
            $this->publisher->schedule(
                Product::MODEL,
                0,
                $this->product->getCurrentLastId($enabledStoreId),
                $enabledStoreId
            );
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('synerise/dashboard/index');
    }
}
