<?php

namespace Synerise\Integration\Model\Synchronization;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory;
use Magento\Newsletter\Model\ResourceModel\Subscriber\Collection;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Helper\Config;
use Synerise\Integration\Helper\Customer as CustomerHelper;
use Synerise\Integration\Model\AbstractSynchronization;


Class Subscriber extends AbstractSynchronization
{
    const MODEL = 'subscriber';
    const ENTITY_ID = 'subscriber_id';
    const CONFIG_XML_PATH_CRON_ENABLED = 'synerise/subscriber/cron_enabled';

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var CustomerHelper
     */
    protected $customerHelper;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        ResourceConnection $resource,
        CollectionFactory $collectionFactory,
        CustomerHelper $customerHelper,
        DateTime $dateTime
    ) {
        $this->customerHelper = $customerHelper;
        $this->dateTime = $dateTime;

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

    public function sendItems($collection, $status)
    {
        $this->customerHelper->addCustomerSubscriptionsBatch($collection);
        $this->markItemsAsSent($collection->getAllIds());
    }

    public function markItemsAsSent($ids)
    {
        $timestamp = $this->dateTime->gmtDate();
        $data = [];
        foreach($ids as $id) {
            $data[] = [
                'synerise_updated_at' => $timestamp,
                'subscriber_id' => $id
            ];
        }

        $this->connection->insertOnDuplicate(
            $this->connection->getTableName('synerise_sync_subscriber'),
            $data
        );
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
