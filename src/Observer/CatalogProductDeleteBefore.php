<?php

namespace Synerise\Integration\Observer;

use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Synerise\ApiClient\ApiException as DefaultApiException;
use Synerise\CatalogsApiClient\ApiException;
use Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\MessageQueue\Publisher\Event as Publisher;
use Synerise\Integration\SyneriseApi\Sender\Data\Product as Sender;

class CatalogProductDeleteBefore implements ObserverInterface
{
    const EVENT = 'catalog_product_delete_before';

    const EVENT_FOR_CONFIG = 'catalog_product_delete_after';

    /**
     * @var Sender
     */
    protected $sender;

    /**
     * @var Synchronization
     */
    protected $synchronizationHelper;

    /**
     * @var Tracking
     */
    protected $trackingHelper;

    /**
     * @var Publisher
     */
    protected $publisher;

    public function __construct(
        Synchronization $synchronizationHelper,
        Tracking $trackingHelper,
        Publisher $publisher,
        Sender $sender
    ) {
        $this->synchronizationHelper = $synchronizationHelper;
        $this->trackingHelper = $trackingHelper;
        $this->publisher = $publisher;
        $this->sender = $sender;
    }

    public function execute(Observer $observer)
    {
        if (!$this->trackingHelper->isEventTrackingEnabled(self::EVENT_FOR_CONFIG)) {
            return;
        }

        /** @var Product $product */
        $product = $observer->getEvent()->getProduct();

        try {
            $enabledCatalogStores = $this->synchronizationHelper->getEnabledStores();
            $productStores = $product->getStoreIds();
            foreach ($productStores as $storeId) {
                if (in_array($storeId, $enabledCatalogStores)) {

                    $addItemRequest = $this->sender->prepareItemRequest(
                        $product,
                        $this->synchronizationHelper->getWebsiteIdByStoreId($storeId)
                    );
                    $addItemRequest->setValue(array_merge($addItemRequest->getValue(), ['deleted' => 1]));

                    if ($this->trackingHelper->isQueueAvailable(self::EVENT_FOR_CONFIG, $storeId)) {
                        $this->publisher->publish(self::EVENT, $addItemRequest, $storeId, $product->getEntityId());
                    } else {
                        $this->sender->deleteItem($addItemRequest, $storeId, $product->getEntityId());
                    }
                }
            }
        } catch (\Exception $e) {
            if (!$e instanceof ApiException && !$e instanceof DefaultApiException) {
                $this->trackingHelper->getLogger()->error($e);
            }
        }
    }
}
