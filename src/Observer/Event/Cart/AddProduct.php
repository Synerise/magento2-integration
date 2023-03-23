<?php

namespace Synerise\Integration\Observer\Event\Cart;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\ValidatorException;
use Magento\Quote\Model\Quote\Item;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\ClientaddedproducttocartRequest;

class AddProduct extends AbstractCartEvent implements ObserverInterface
{
    const EVENT = 'checkout_cart_add_product_complete';

    public function execute(Observer $observer)
    {
        if (!$this->isLiveEventTrackingEnabled(static::EVENT)) {
            return;
        }

        if ($this->identityHelper->isAdminStore()) {
            return;
        }

        try {
            /** @var Item $quoteItem */
            $quoteItem = $observer->getQuoteItem();
            if ($quoteItem->getProduct()->getParentProductId()) {
                return;
            }

            $request = $this->cartHelper->prepareAddToCartRequest(
                $quoteItem,
                static::EVENT,
                $this->identityHelper->getClientUuid()
            );

            $this->publishOrSendEvent(static::EVENT, $request, $quoteItem->getStoreId());

        } catch (\Exception $e) {
            $this->logger->error('Synerise Error', ['exception' => $e]);
        }
    }

    /**
     * @param string $eventName
     * @param ClientaddedproducttocartRequest $request
     * @param int $storeId
     * @return void
     * @throws ValidatorException
     */
    public function publishOrSendEvent(string $eventName, ClientaddedproducttocartRequest $request, int $storeId): void
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
