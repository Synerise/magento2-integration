<?php

namespace Synerise\Integration\Observer\Event\Wishlist;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\ValidatorException;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CustomeventRequest;

class RemoveProduct  extends AbstractWishlistEvent implements ObserverInterface
{
    const EVENT = 'wishlist_item_delete_after';

    public function execute(Observer $observer)
    {
        if (!$this->isLiveEventTrackingEnabled(self::EVENT)) {
            return;
        }

        if ($this->identityHelper->isAdminStore()) {
            return;
        }

        try {
            $request = $this->favoritesHelper->prepareClientRemovedProductFromFavoritesRequest(
                self::EVENT,
                $observer->getEvent()->getItem()->getProduct(),
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
     * @param CustomeventRequest $request
     * @param int $storeId
     * @return void
     * @throws ValidatorException
     */
    public function publishOrSendEvent(string $eventName, CustomeventRequest $request, int $storeId): void
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
