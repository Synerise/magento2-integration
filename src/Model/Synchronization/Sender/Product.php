<?php

namespace Synerise\Integration\Model\Synchronization\Sender;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Synerise\Integration\Helper\Catalog as CatalogHelper;
use Synerise\Integration\Helper\Queue;

class Product implements SenderInterface
{
    const MODEL = 'product';
    const ENTITY_ID = 'entity_id';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var catalogHelper
     */
    protected $catalogHelper;


    public function __construct(
        ScopeConfigInterface $scopeConfig,
        CatalogHelper $catalogHelper
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->catalogHelper = $catalogHelper;
    }

    /**
     * @param Collection $collection
     * @param int $storeId
     * @param int|null $websiteId
     * @return void
     * @throws \Exception
     */
    public function sendItems($collection, int $storeId, ?int $websiteId = null)
    {
        /* @todo: move to collection creation */
        $attributes = $this->catalogHelper->getProductAttributesToSelect($storeId);
        $collection
            ->addAttributeToSelect($attributes);

        /* @todo: move to current class */
        $this->catalogHelper->addItemsBatchWithCatalogCheck(
            $collection,
            $attributes,
            $websiteId,
            $storeId
        );
    }

    /**
     * @param int|null $storeId
     * @return mixed
     */
    public function getPageSize(?int $storeId = null): int
    {
        return $this->scopeConfig->getValue(
            Queue::XML_PATH_CRON_STATUS_PAGE_SIZE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
