<?php

namespace Synerise\Integration\Observer;

use Magento\Catalog\Model\Product;
use Magento\Framework\Event\ObserverInterface;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Helper\Api;
use Synerise\Integration\Helper\Event;
use Synerise\Integration\Helper\Queue;
use Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\Model\Synchronization\Sender\Product as Sender;

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
     * @var Queue
     */
    protected $queueHelper;

    /**
     * @var Event
     */
    protected $eventHelper;

    public function __construct(
        Sender $sender,
        Api $apiHelper,
        Tracking $trackingHelper,
        Synchronization $synchronizationHelper,
        Queue $queueHelper,
        Event $eventHelper
    ) {
        $this->sender = $sender;
        $this->apiHelper = $apiHelper;
        $this->trackingHelper = $trackingHelper;
        $this->synchronizationHelper = $synchronizationHelper;
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
            $enabledCatalogStores = $this->synchronizationHelper->getEnabledStores();
            $productStores = $product->getStoreIds();
            foreach ($productStores as $storeId) {
                if (in_array($storeId, $enabledCatalogStores)) {

                    $addItemRequest = $this->sender->prepareItemRequest(
                        $product,
                        $this->synchronizationHelper->getWebsiteIdByStoreId($storeId)
                    );
                    $addItemRequest->setValue(array_merge($addItemRequest->getValue(), ['deleted' => 1]));

                    if ($this->queueHelper->isQueueAvailable(self::EVENT, $storeId)) {
                        $this->queueHelper->publishEvent(self::EVENT, [$addItemRequest], $storeId);
                    } else {
                        $this->eventHelper->sendEvent(self::EVENT, [$addItemRequest], $storeId);
                    }
                }
            }
        } catch (ApiException $e) {
        } catch (\Exception $e) {
            $this->trackingHelper->getLogger()->error($e);
        }
    }
}
