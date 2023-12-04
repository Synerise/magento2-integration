<?php

namespace Synerise\Integration\Observer;

use Magento\Catalog\Model\Product;
use Magento\Framework\Event\ObserverInterface;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Helper\Api;
use Synerise\Integration\MessageQueue\Sender\Event;
use Synerise\Integration\MessageQueue\Publisher\Event as Publisher;
use Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\MessageQueue\Sender\Data\Product as Sender;

class CatalogProductDeleteBefore implements ObserverInterface
{
    const EVENT = 'catalog_product_delete_after';

    /**
     * @var Sender
     */
    protected $sender;

    /**
     * @var Api
     */
    protected $apiHelper;

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

    /**
     * @var Event
     */
    protected $event;

    public function __construct(
        Sender $sender,
        Api $apiHelper,
        Tracking $trackingHelper,
        Synchronization $synchronizationHelper,
        Publisher $publisher,
        Event $event
    ) {
        $this->event = $event;
        $this->apiHelper = $apiHelper;
        $this->trackingHelper = $trackingHelper;
        $this->synchronizationHelper = $synchronizationHelper;
        $this->publisher = $publisher;
        $this->sender = $sender;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->trackingHelper->isEventTrackingEnabled(self::EVENT)) {
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

                    if ($this->trackingHelper->isQueueAvailable(self::EVENT, $storeId)) {
                        $this->publisher->publish(self::EVENT, [$addItemRequest], $storeId);
                    } else {
                        $this->event->send(self::EVENT, [$addItemRequest], $storeId);
                    }
                }
            }
        } catch (ApiException $e) {
        } catch (\Exception $e) {
            $this->trackingHelper->getLogger()->error($e);
        }
    }
}
