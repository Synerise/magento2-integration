<?php

namespace Synerise\Integration\Model\Synchronization\Sender;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Synerise\Integration\Helper\Order as OrderHelper;
use Synerise\Integration\Helper\Queue;

class Order implements SenderInterface
{
    const MODEL = 'order';
    const ENTITY_ID = 'entity_id';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        OrderHelper $orderHelper
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->orderHelper = $orderHelper;
    }

    /**
     * @param Collection $collection
     * @param int $storeId
     * @param int|null $websiteId
     * @return void
     * @throws \Synerise\ApiClient\ApiException
     */
    public function sendItems($collection, int $storeId, ?int $websiteId = null)
    {
        $collection->addAttributeToSelect($this->orderHelper->getAttributesToSelect());

        $ids = $this->orderHelper->addOrdersBatch($collection, $storeId);
        if($ids) {
            $this->orderHelper->markItemsAsSent($ids);
        }
    }

    /**
     * @param int|null $storeId
     * @return int
     */
    public function getPageSize(?int $storeId = null): int
    {
        return (int) $this->scopeConfig->getValue(
            Queue::XML_PATH_CRON_STATUS_PAGE_SIZE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
