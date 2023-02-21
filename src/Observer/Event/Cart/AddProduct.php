<?php

namespace Synerise\Integration\Observer\Event\Cart;

use Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote\Item;
use Synerise\ApiClient\Model\ClientaddedproducttocartRequest;

class AddProduct extends AbstractCartEvent implements ObserverInterface
{
    const EVENT = 'checkout_cart_add_product_complete';

    public function execute(Observer $observer)
    {
        if (!$this->isLiveEventTrackingEnabled(self::EVENT)) {
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

            $this->sendAddToCartEvent(
                $this->cartHelper->prepareAddToCartRequest(
                    $quoteItem,
                    self::EVENT,
                    $this->identityHelper->getClientUuid()
                )
            );
        } catch (Exception $e) {
            $this->logger->error('Synerise Api request failed', ['exception' => $e]);
        }
    }

    /**
     * @param ClientaddedproducttocartRequest $request
     * @param int|null $storeId
     * @return array
     * @throws \Magento\Framework\Exception\ValidatorException
     * @throws \Synerise\ApiClient\ApiException
     */
    public function sendAddToCartEvent(ClientaddedproducttocartRequest $request, ?int $storeId = null): array
    {
        return $this->getDefaultApiInstance($storeId)
            ->clientAddedProductToCartWithHttpInfo('4.4', $request);
    }
}
