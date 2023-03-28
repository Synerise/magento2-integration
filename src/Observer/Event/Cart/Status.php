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
                $request = $this->cartHelper->prepareCartStatusRequest(
                    $quote,
                    $this->identityHelper->getClientUuid()
                );

                $this->publishOrSendEvent(self::EVENT, $request, $quote->getStoreId());
            }
        } catch (Exception $e) {
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
