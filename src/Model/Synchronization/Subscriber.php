<?php

namespace Synerise\Integration\Model\Synchronization;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Helper\Customer as CustomerHelper;
use Synerise\Integration\Model\AbstractSynchronization;

class Subscriber extends AbstractSynchronization
{
    const MODEL = 'subscriber';
    const ENTITY_ID = 'subscriber_id';
    const CONFIG_XML_PATH_CRON_ENABLED = 'synerise/subscriber/cron_enabled';

    /**
     * @var CustomerHelper
     */
    protected $customerHelper;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        ResourceConnection $resource,
        CollectionFactory $collectionFactory,
        CustomerHelper $customerHelper
    ) {
        $this->customerHelper = $customerHelper;

        parent::__construct(
            $scopeConfig,
            $logger,
            $resource,
            $collectionFactory
        );
    }

    public function getCollectionFilteredByIdRange($storeId, $startId, $stopId)
    {
        return parent::getCollectionFilteredByIdRange($storeId, $startId, $stopId)
            ->addFieldToSelect(['subscriber_email', 'subscriber_status', 'change_status_at']);
    }

    /**
     * @param \Magento\Newsletter\Model\ResourceModel\Subscriber\Collection $collection
     * @param \Synerise\Integration\Model\Cron\Status $status
     * @throws \Synerise\ApiClient\ApiException
     */
    public function sendItems($collection, $status)
    {
        $this->customerHelper->addCustomerSubscriptionsBatch($collection);
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
