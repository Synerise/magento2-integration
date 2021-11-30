<?php

namespace Synerise\Integration\Model\Synchronization\Subject;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Helper\Customer as CustomerHelper;

class Subscriber extends AbstractSubject
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
    }

    public function markAllAsUnsent()
    {
        $this->connection->truncateTable($this->connection->getTableName('synerise_sync_subscriber'));
    }
}
