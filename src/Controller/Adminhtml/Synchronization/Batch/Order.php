<?php

namespace Synerise\Integration\Controller\Adminhtml\Synchronization\Batch;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\MessageQueue\Publisher\Data\Batch as Publisher;
use Synerise\Integration\SyneriseApi\Sender\Data\Order as Sender;

class Order extends Action
{
    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'Synerise_Integration::synchronization_order';

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
     * @return Redirect
     * @throws LocalizedException | Exception
     */
    public function execute()
    {
        if (!$this->synchronization->isSynchronizationEnabled()) {
            $this->messageManager->addErrorMessage(
                __('Synchronization is disabled. Please review your configuration.')
            );
        } elseif (!$this->synchronization->isEnabledModel(\Synerise\Integration\SyneriseApi\Sender\Data\Customer::MODEL)) {
            $this->messageManager->addErrorMessage(
                __('%1s are excluded from synchronization.', ucfirst(Sender::MODEL))
            );
        } else {            $storeIds = [];
            $itemsCount = 0;
            $enabledStoreIds = $this->synchronization->getEnabledStores();
            /** @var Collection $collection */
            $collection = $this->filter->getCollection($this->collectionFactory->create());

            try {
                foreach ($enabledStoreIds as $enabledStoreId) {
                    $collection->addFieldToFilter('store_id', ['eq' => $enabledStoreId]);
                    $ids = $collection->getAllIds();
                    if (!empty($ids)) {
                        $this->publisher->schedule(
                            Sender::MODEL,
                            $collection->getAllIds(),
                            $enabledStoreId,
                            null,
                            $this->synchronization->getPageSize(\Synerise\Integration\SyneriseApi\Sender\Data\Customer::MODEL)
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
                        __(
                            'Nothing to synchronize. No stores enabled for selected %1(s).',
                            Sender::MODEL
                        )
                    );
                }
            } catch (Exception $e) {
                $this->logger->error('Failed to add records to synchronization queue', ['exception' => $e]);
                $this->messageManager->addErrorMessage(__('Failed to add records to synchronization queue'));
            }
        }

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('sales/order/index');
    }
}
