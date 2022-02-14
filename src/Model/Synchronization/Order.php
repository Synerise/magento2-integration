<?php

namespace Synerise\Integration\Model\Synchronization;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Helper\Config;
use Synerise\Integration\Helper\Order as OrderHelper;
use Synerise\Integration\Model\AbstractSynchronization;


Class Order extends AbstractSynchronization
{
    const MODEL = 'order';
    const ENTITY_ID = 'entity_id';
    const CONFIG_XML_PATH_CRON_ENABLED = 'synerise/order/cron_enabled';

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        ResourceConnection $resource,
        CollectionFactory $collectionFactory,
        OrderHelper $orderHelper,
        DateTime $dateTime
    ) {
        $this->orderHelper = $orderHelper;
        $this->dateTime = $dateTime;

        parent::__construct(
            $scopeConfig,
            $logger,
            $resource,
            $collectionFactory
        );
    }

    public function sendItems($collection, $status)
    {
        $attributes = $this->orderHelper->getAttributesToSelect();
        $collection->addAttributeToSelect($attributes);

        $this->orderHelper->addOrdersBatch($collection);
        $this->orderHelper->markItemsAsSent($collection->getAllIds());
    }

    public function markAllAsUnsent()
    {
        $this->connection->truncateTable($this->connection->getTableName('synerise_sync_order'));
    }
}
