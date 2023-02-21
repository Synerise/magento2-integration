<?php

namespace Synerise\Integration\Observer\Event\Wishlist;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Synerise\ApiClient\Api\DefaultApi;
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
            $this->sendClientAddedProductToFavoritesEvent(
                $this->favoritesHelper->prepareClientAddedProductToFavoritesRequest(
                    self::EVENT,
                    $observer->getEvent()->getProduct(),
                    $this->identityHelper->getClientUuid()
                )
            );
        } catch (\Exception $e) {
            $this->logger->error('Synerise Api request failed', ['exception' => $e]);
        }
    }

    /**
     * @param EventClientAction $request
     * @param int|null $storeId
     * @return array
     * @throws \Magento\Framework\Exception\ValidatorException
     * @throws \Synerise\ApiClient\ApiException
     */
    public function sendClientAddedProductToFavoritesEvent(EventClientAction $request, ?int $storeId = null): array
    {
        return $this->getDefaultApiInstance($storeId)
            ->clientAddedProductToFavoritesWithHttpInfo('4.4', $request);
    }

    public function getDefaultApiInstance(?int $storeId = null): DefaultApi
    {
        return $this->defaultApiFactory->get($this->apiHelper->getApiConfigByScope($storeId));
    }
}
