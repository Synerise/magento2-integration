<?php

namespace Synerise\Integration\Controller\Adminhtml\Catalog;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\Model\Synchronization\MessageQueue\Data\Batch\Publisher;
use Synerise\Integration\Model\Synchronization\Sender\Product as Sender;

class ScheduleMass extends Action
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
     * @var Synchronization
     */
    private $synchronizationHelper;

    public function __construct(
        Context $context,
        LoggerInterface $logger,
        Filter $filter,
        CollectionFactory $collectionFactory,
        Publisher $publisher,
        Synchronization $synchronizationHelper
    ) {
        $this->logger = $logger;
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->publisher = $publisher;
        $this->synchronizationHelper = $synchronizationHelper;

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
        $storeIds = [];
        $itemsCount = 0;
        $enabledStoreIds = $this->synchronizationHelper->getEnabledStores();
        /** @var Collection $collection */
        $collection = $this->filter->getCollection($this->collectionFactory->create());

        try {
            foreach ($enabledStoreIds as $enabledStoreId) {
                $collection->addStoreFilter($enabledStoreId);
                $ids = $collection->getAllIds();
                if (!empty($ids)) {
                    $this->publisher->schedule(
                        Sender::MODEL,
                        $collection->getAllIds(),
                        $enabledStoreId,
                        $this->synchronizationHelper->getWebsiteIdByStoreId($enabledStoreId)
                    );
                    $storeIds[] = $enabledStoreId;
                    $itemsCount += $collection->getSize();
                }
            }

            if (!empty($storeIds)) {
                $this->messageManager->addSuccessMessage(
                    __(
                        'A total of %1 %2(s) have been added to synchronization queue for stores: %3',
                        $itemsCount,
                        Sender::MODEL,
                        implode(',',$storeIds)
                    )
                );
            } else {
                $this->messageManager->addErrorMessage(
                    __('Nothing to synchronize. Stores for selected product(s) not enabled for synchronization.')
                );
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to schedule records to synchronization queue', ['exception' => $e]);
            $this->messageManager->addErrorMessage(__('Failed to add records to synchronization queue'));
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('catalog/product/index');
    }
}
