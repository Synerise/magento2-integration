<?php

namespace Synerise\Integration\Controller\Adminhtml\Catalog;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Helper\Queue;
use Synerise\Integration\Model\MessageQueue\Data\Batch\Publisher;
use Synerise\Integration\Model\Synchronization\Product;

class MassSchedule extends Action
{
    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'Synerise_Integration::synchronization_catalog';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

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
        Filter $filter,
        CollectionFactory $collectionFactory,
        Publisher $publisher,
        Queue $queueHelper,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->publisher = $publisher;
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
        $collection = $this->filter->getCollection($this->collectionFactory->create());

        try {
            foreach ($enabledStoreIds as $enabledStoreId) {
                $this->publisher->schedule(Product::MODEL, $enabledStoreId, $collection->getAllIds());
                $this->messageManager->addSuccessMessage(
                    __('A total of %1 products(s) have been added to synchronization queue for all enabled stores.', $collection->getSize())
                );
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to add records to synchronization queue', ['exception' => $e]);
            $this->messageManager->addErrorMessage(__('Failed to add records to synchronization queue'));
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('catalog/product/index');
    }
}
