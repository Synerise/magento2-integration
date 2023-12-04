<?php

namespace Synerise\Integration\Controller\Adminhtml\Customer;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Customer\Model\ResourceModel\Customer\Collection;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\MessageQueue\Publisher\Data\Batch as Publisher;
use Synerise\Integration\MessageQueue\Sender\Data\Customer as Sender;

class ScheduleMass extends Action
{
    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'Synerise_Integration::synchronization_customer';

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
    private $synchronization;

    public function __construct(
        Context $context,
        LoggerInterface $logger,
        Filter $filter,
        CollectionFactory $collectionFactory,
        Publisher $publisher,
        Synchronization $synchronization
    ) {
        $this->logger = $logger;
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->publisher = $publisher;
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
        if ($this->synchronization->isEnabledModel(Sender::MODEL)) {
            $storeIds = [];
            $itemsCount = 0;
            $enabledStoreIds = $this->synchronization->getEnabledStores();

            try {
                foreach ($enabledStoreIds as $enabledStoreId) {
                    /** @var Collection $collection */
                    $collection = $this->filter->getCollection($this->collectionFactory->create())
                        ->addFieldToFilter('store_id', ['eq' => $enabledStoreId]);

                    $ids = $collection->getAllIds();
                    if (!empty($ids)) {
                        $this->publisher->schedule(
                            Sender::MODEL,
                            $collection->getAllIds(),
                            $enabledStoreId,
                            null,
                            $this->synchronization->getPageSize(Sender::MODEL)
                        );
                        $storeIds[] = $enabledStoreId;
                        $itemsCount += $collection->count();
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
                        __(
                            'Nothing to synchronize. No stores enabled for selected %1(s).',
                            Sender::MODEL
                        )
                    );
                }
            } catch (\Exception $e) {
                $this->logger->error('Failed to add records to synchronization queue', ['exception' => $e]);
                $this->messageManager->addErrorMessage(__('Failed to add records to synchronization queue'));
            }
        } else {
            $this->messageManager->addErrorMessage(
                __('%1s are excluded from synchronization.', ucfirst(Sender::MODEL))
            );
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('customer/index/index');
    }
}
