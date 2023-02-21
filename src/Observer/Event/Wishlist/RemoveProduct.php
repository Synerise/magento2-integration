<?php

namespace Synerise\Integration\Observer\Event\Wishlist;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\ValidatorException;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CustomeventRequest;
use Synerise\Integration\Observer\Event\Cart\AbstractWishlistEvent;

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
            $this->sendCustomEvent(
                $this->favoritesHelper->prepareClientRemovedProductFromFavoritesRequest(
                    self::EVENT,
                    $observer->getEvent()->getItem()->getProduct(),
                    $this->identityHelper->getClientUuid()
                )
            );
        } catch (\Exception $e) {
            $this->logger->error('Synerise Api request failed', ['exception' => $e]);
        }
    }

    /**
     * @param CustomeventRequest $request
     * @param int|null $storeId
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     * @throws ApiException
     * @throws ValidatorException
     */
    public function sendCustomEvent(CustomeventRequest $request, ?int $storeId = null): array
    {
        return $this->getDefaultApiInstance($storeId)
            ->customEventWithHttpInfo('4.4', $request);
    }
}
