<?php

namespace Synerise\Integration\Model\Synchronization;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory;
use Synerise\Integration\Helper\Customer as CustomerHelper;
use Synerise\Integration\Helper\Queue;
use Synerise\Integration\Model\AbstractSynchronization;

class Subscriber extends AbstractSynchronization
{
    const MODEL = 'subscriber';
    const ENTITY_ID = 'subscriber_id';

    /**
     * @var CustomerHelper
     */
    protected $customerHelper;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ResourceConnection $resource,
        Queue $queueHelper,
        CollectionFactory $collectionFactory,
        CustomerHelper $customerHelper
    ) {
        $this->customerHelper = $customerHelper;

        parent::__construct(
            $scopeConfig,
            $resource,
            $queueHelper,
            $collectionFactory
        );
    }

    public function getCollectionFilteredByIdRange($storeId, $websiteId, $gt, $le)
    {
        $collection = parent::getCollectionFilteredByIdRange($storeId, $websiteId, $gt, $le)
            ->addFieldToSelect(['subscriber_email', 'subscriber_status', 'change_status_at']);

        return $collection;
    }

    /**
     * @param int $storeId
     * @param int|null $websiteId
     * @return mixed
     */
    protected function createCollectionWithScope($storeId, $websiteId = null)
    {
        $collection = $this->collectionFactory->create();
        $collection->getSelect()
            ->where('main_table.store_id=?', $storeId);

        return $collection;
    }

    /**
     * @param \Magento\Newsletter\Model\ResourceModel\Subscriber\Collection $collection
     * @param int $storeId
     * @param int|null $websiteId
     * @throws \Synerise\ApiClient\ApiException
     */
    public function sendItems($collection, $storeId, $websiteId = null)
    {
        $this->customerHelper->addCustomerSubscriptionsBatch($collection, $storeId);
        $this->customerHelper->markSubscribersAsSent($collection->getAllIds());
    }

    public function markAllAsUnsent()
    {
        $this->connection->truncateTable($this->connection->getTableName('synerise_sync_subscriber'));
    }

    public function countAll()
    {
        $select = $this->connection->select()
            ->from($this->connection->getTableName('synerise_sync_subscriber'), 'COUNT(*)');

        return (int)$this->connection->fetchOne($select);
    }
}
