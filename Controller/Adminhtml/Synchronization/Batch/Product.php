<?php

namespace Synerise\Integration\Controller\Adminhtml\Synchronization\Batch;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\MassAction\Filter;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\MessageQueue\Publisher\Data\Batch as Publisher;
use Synerise\Integration\Model\Synchronization\Config;
use Synerise\Integration\SyneriseApi\Sender\Data\Product as Sender;

class Product extends Action
{
    /**
     * Authorization level
     */
    public const ADMIN_RESOURCE = 'Synerise_Integration::synchronization_catalog';

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Logger
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
     * @var Config
     */
    private $synchronization;

    /**
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param Logger $logger
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param Publisher $publisher
     * @param Config $synchronization
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Logger $logger,
        Filter $filter,
        CollectionFactory $collectionFactory,
        Publisher $publisher,
        Config $synchronization
    ) {
        $this->storeManager = $storeManager;
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
        if (!$this->synchronization->isEnabled()) {
            $this->messageManager->addErrorMessage(
                __('Synchronization is disabled. Please review your configuration.')
            );
        } elseif (!$this->synchronization->isModelEnabled(Sender::MODEL)) {
            $this->messageManager->addErrorMessage(
                __('%1s are excluded from synchronization.', ucfirst(Sender::MODEL))
            );
        } else {
            $storeIds = [];
            $itemsCount = 0;
            $enabledStoreIds = $this->synchronization->getConfiguredStores();
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
                            $this->getWebsiteIdByStoreId($enabledStoreId)
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
                            implode(',', $storeIds)
                        )
                    );
                } else {
                    $this->messageManager->addErrorMessage(
                        __('Nothing to synchronize. Stores for selected product(s) not enabled for synchronization.')
                    );
                }
            } catch (Exception $e) {
                $this->logger->error('Failed to schedule records to synchronization queue', ['exception' => $e]);
                $this->messageManager->addErrorMessage(__('Failed to add records to synchronization queue'));
            }
        }

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('catalog/product/index');
    }

    /**
     * Get website ID by store ID
     *
     * @param int $storeId
     * @return int
     * @throws NoSuchEntityException
     */
    public function getWebsiteIdByStoreId(int $storeId): int
    {
        return $this->storeManager->getStore($storeId)->getWebsiteId();
    }
}
