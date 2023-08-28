<?php

namespace Synerise\Integration\Observer;

use Magento\Catalog\Model\Product;
use Magento\Framework\Event\ObserverInterface;
use Synerise\ApiClient\ApiException;

class CatalogProductDeleteBefore implements ObserverInterface
{
    const EVENT = 'catalog_product_delete_after';

    /**
     * @var \Synerise\Integration\Helper\Api
     */
    protected $apiHelper;

    /**
     * @var \Synerise\Integration\Helper\Catalog
     */
    protected $catalogHelper;

    /**
     * @var \Synerise\Integration\Helper\Tracking
     */
    protected $trackingHelper;

    /**
     * @var \Synerise\Integration\Helper\Queue
     */
    protected $queueHelper;

    /**
     * @var \Synerise\Integration\Helper\Event
     */
    protected $eventHelper;

    public function __construct(
        \Synerise\Integration\Helper\Api $apiHelper,
        \Synerise\Integration\Helper\Catalog $catalogHelper,
        \Synerise\Integration\Helper\Tracking $trackingHelper,
        \Synerise\Integration\Helper\Queue $queueHelper,
        \Synerise\Integration\Helper\Event $eventHelper
    ) {
        $this->apiHelper = $apiHelper;
        $this->catalogHelper = $catalogHelper;
        $this->trackingHelper = $trackingHelper;
        $this->queueHelper = $queueHelper;
        $this->eventHelper = $eventHelper;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->trackingHelper->isEventTrackingEnabled(self::EVENT)) {
            return;
        }

        /** @var Product $product */
        $product = $observer->getEvent()->getProduct();

        try {
            $enabledCatalogStores = $this->catalogHelper->getStoresForCatalogs();
            $productStores = $product->getStoreIds();
            foreach ($productStores as $storeId) {
                if (in_array($storeId, $enabledCatalogStores)) {
                    $attributes = $this->catalogHelper->getProductAttributesToSelect($product->getStoreId());
                    $addItemRequest = $this->catalogHelper->prepareItemRequest($product, $attributes);
                    $addItemRequest->setValue(array_merge($addItemRequest->getValue(), ['deleted' => 1]));

                    if ($this->queueHelper->isQueueAvailable(self::EVENT, $storeId)) {
                        $this->queueHelper->publishEvent(self::EVENT, $addItemRequest, $storeId);
                    } else {
                        $this->eventHelper->sendEvent(self::EVENT, $addItemRequest, $storeId);
                    }
                }
            }
        } catch (ApiException $e) {
        } catch (\Exception $e) {
            $this->trackingHelper->getLogger()->error($e);
        }
    }
}
