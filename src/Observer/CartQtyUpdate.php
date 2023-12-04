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
     * @var \Synerise\Integration\MessageQueue\Publisher\Event
     */
    protected $publisher;

    /**
     * @var \Synerise\Integration\MessageQueue\Sender\Event
     */
    protected $sender;

    /**
     * @var \Synerise\Integration\Helper\Cart
     */
    protected $cartHelper;

    public function __construct(
        \Synerise\Integration\Helper\Catalog $catalogHelper,
        \Synerise\Integration\Helper\Tracking $trackingHelper,
        \Synerise\Integration\MessageQueue\Publisher\Event $publisher,
        \Synerise\Integration\MessageQueue\Sender\Event $sender,
        \Synerise\Integration\Helper\Cart $cartHelper
    ) {
        $this->catalogHelper = $catalogHelper;
        $this->trackingHelper = $trackingHelper;
        $this->publisher = $publisher;
        $this->sender = $sender;
        $this->cartHelper = $cartHelper;
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
                $cartStatusEvent = $this->cartHelper->prepareCartStatusEvent(
                    $quote,
                    (float) $quote->getSubtotal(),
                    (int) $quote->getItemsQty()
                );

                if ($this->trackingHelper->isQueueAvailable(self::EVENT, $storeId)) {
                    $this->publisher->publish(self::EVENT, $cartStatusEvent, $storeId);
                } else {
                    $this->sender->send(self::EVENT, $cartStatusEvent, $storeId);
                }
            }
        } catch (ApiException $e) {
        } catch (\Exception $e) {
            $this->trackingHelper->getLogger()->error($e);
        }
    }
}
