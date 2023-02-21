<?php

namespace Synerise\Integration\Observer\Event\Cart;

use Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\ValidatorException;
use Magento\Quote\Model\Quote;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CustomeventRequest;

class Status  extends AbstractCartEvent implements ObserverInterface
{
    const EVENT = 'sales_quote_save_after';

    public function execute(Observer $observer)
    {
        if (!$this->isLiveEventTrackingEnabled(static::EVENT)) {
            return;
        }

        if ($this->identityHelper->isAdminStore()) {
            return;
        }

        try {
            /** @var Quote $quote */
            $quote = $observer->getQuote();
            $quote->collectTotals();

            if ($this->cartHelper->hasItemDataChanges($quote)) {
                $this->sendCartStatusEvent(
                    $this->cartHelper->prepareCartStatusRequest(
                        $quote,
                        $this->identityHelper->getClientUuid()
                    )
                );
            }
        } catch (Exception $e) {
            $this->logger->error('Synerise Api request failed', ['exception' => $e]);
        }
    }

    /**
     * @param CustomeventRequest $customEventRequest
     * @param int|null $storeId
     * @return array|null
     * @throws ApiException
     * @throws ValidatorException
     */
    public function sendCartStatusEvent(CustomeventRequest $customEventRequest, ?int $storeId = null): ?array
    {
        $response = null;
        if (!$this->cartHelper->isCartStatusSent()) {
            $response = $this->getDefaultApiInstance($storeId)
                ->customEventWithHttpInfo('4.4', $customEventRequest);
            $this->cartHelper->setCartStatusSent(true);
        }

        return $response;
    }
}
