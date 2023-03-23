<?php

namespace Synerise\Integration\Observer\Event\Wishlist;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\ValidatorException;
use Synerise\ApiClient\Api\DefaultApi;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\ClientaddedproducttocartRequest;
use Synerise\ApiClient\Model\EventClientAction;
use Synerise\Integration\Observer\Event\Cart\AbstractWishlistEvent;

class AddProduct extends AbstractWishlistEvent implements ObserverInterface
{
    const EVENT = 'wishlist_add_product';

    public function execute(Observer $observer)
    {
        if (!$this->isLiveEventTrackingEnabled(self::EVENT)) {
            return;
        }

        if ($this->identityHelper->isAdminStore()) {
            return;
        }

        try {
            $request = $this->favoritesHelper->prepareClientAddedProductToFavoritesRequest(
                self::EVENT,
                $observer->getEvent()->getProduct(),
                $this->identityHelper->getClientUuid()
            );
            $params = $request->getParams();

            $this->publishOrSendEvent(self::EVENT, $request, $params['storeId']);
        } catch (\Exception $e) {
            $this->logger->error('Synerise Error', ['exception' => $e]);
        }
    }

    /**
     * @param string $eventName
     * @param EventClientAction $request
     * @param int $storeId
     * @return void
     * @throws ValidatorException
     */
    public function publishOrSendEvent(string $eventName, EventClientAction $request, int $storeId): void
    {
        try {
            if ($this->queueHelper->isQueueAvailable()) {
                $this->queueHelper->publishEvent($eventName, $request, $storeId);
            } else {
                $this->eventsHelper->sendEvent($eventName, $request, $storeId);
            }
        } catch (ApiException $e) {
        }
    }
}
