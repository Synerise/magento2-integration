<?php

namespace Synerise\Integration\Model\Synchronization\Subject;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Helper\Order as OrderHelper;

class Order extends AbstractSubject
{
    const MODEL = 'order';
    const ENTITY_ID = 'entity_id';
    const CONFIG_XML_PATH_CRON_ENABLED = 'synerise/order/cron_enabled';

    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        ResourceConnection $resource,
        CollectionFactory $collectionFactory,
        OrderHelper $orderHelper
    ) {
        $this->orderHelper = $orderHelper;

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
        $this->orderHelper->markAllAsUnsent();
    }
}
