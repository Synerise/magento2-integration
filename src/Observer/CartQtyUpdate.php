<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\ValidatorException;
use Synerise\ApiClient\ApiException;

class CartQtyUpdate implements ObserverInterface
{
    const EVENT = 'checkout_cart_update_items_after';

    /**
     * @var \Synerise\Integration\Helper\Catalog
     */
    protected $catalogHelper;

    /**
     * @var \Synerise\Integration\Helper\Tracking
     */
    protected $trackingHelper;

    /**
     * @var \Synerise\Integration\Helper\Queue
     */
    protected $queueHelper;

    /**
     * @var \Synerise\Integration\Helper\Event
     */
    protected $eventHelper;

    public function __construct(
        \Synerise\Integration\Helper\Catalog $catalogHelper,
        \Synerise\Integration\Helper\Tracking $trackingHelper,
        \Synerise\Integration\Helper\Queue $queueHelper,
        \Synerise\Integration\Helper\Event $eventHelper
    ) {
        $this->catalogHelper = $catalogHelper;
        $this->trackingHelper = $trackingHelper;
        $this->queueHelper = $queueHelper;
        $this->eventHelper = $eventHelper;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->trackingHelper->isLiveEventTrackingEnabled(self::EVENT)) {
            return;
        }

        if ($this->trackingHelper->isAdminStore()) {
            return;
        }

        try {
            /** @var \Magento\Quote\Model\Quote $quote */
            $quote = $observer->getCart()->getQuote();
            $storeId = $quote->getStoreId();

            if (!$this->trackingHelper->getClientUuid() && !$quote->getCustomerEmail()) {
                return;
            }

            $quote->collectTotals();

            if (!$this->trackingHelper->hasItemDataChanges($quote)) {
                // quote save won't be triggered, send event.
                $cartStatusEvent = $this->eventHelper->prepareCartStatusEvent(
                    $quote,
                    (float) $quote->getSubtotal(),
                    (int) $quote->getItemsQty()
                );

                if ($this->queueHelper->isQueueAvailable(self::EVENT, $storeId)) {
                    $this->queueHelper->publishEvent(self::EVENT, $cartStatusEvent, $storeId);
                } else {
                    $this->eventHelper->sendEvent(self::EVENT, $cartStatusEvent, $storeId);
                }
            }
        } catch (ApiException $e) {
        } catch (\Exception $e) {
            $this->trackingHelper->getLogger()->error($e);
        }
    }
}
