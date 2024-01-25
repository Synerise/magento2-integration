<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Helper\Cart;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\MessageQueue\Publisher\Event as EventPublisher;
use Synerise\Integration\SyneriseApi\Sender\Event as EventSender;

class CartQtyUpdate implements ObserverInterface
{
    public const EVENT = 'checkout_cart_update_items_after';

    /**
     * @var Cart
     */
    protected $cartHelper;

    /**
     * @var Logger
     */
    protected $loggerHelper;

    /**
     * @var Tracking
     */
    protected $trackingHelper;

    /**
     * @var EventPublisher
     */
    protected $publisher;

    /**
     * @var EventSender
     */
    protected $sender;

    /**
     * @param Cart $cartHelper
     * @param Logger $loggerHelper
     * @param Tracking $trackingHelper
     * @param EventPublisher $publisher
     * @param EventSender $sender
     */
    public function __construct(
        Cart $cartHelper,
        Logger $loggerHelper,
        Tracking $trackingHelper,
        EventPublisher $publisher,
        EventSender $sender
    ) {
        $this->cartHelper = $cartHelper;
        $this->loggerHelper = $loggerHelper;
        $this->trackingHelper = $trackingHelper;
        $this->publisher = $publisher;
        $this->sender = $sender;
    }

    /**
     * Execute
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if ($this->trackingHelper->getContext()->isAdminStore()) {
            return;
        }

        try {
            /** @var \Magento\Quote\Model\Quote $quote */
            $quote = $observer->getCart()->getQuote();
            $storeId = $quote->getStoreId();

            if (!$this->trackingHelper->isEventTrackingAvailable(self::EVENT, $storeId)) {
                return;
            }

            if (!$this->trackingHelper->getClientUuid() && !$quote->getCustomerEmail()) {
                return;
            }

            $quote->collectTotals();
            $totals = $quote->getTotals();

            if (!$this->cartHelper->hasItemDataChanges($quote)) {
                // quote save won't be triggered, send event.
                $cartStatusEvent = $this->cartHelper->prepareCartStatusEvent(
                    $quote,
                    isset($totals['subtotal']) ? (double) $totals['subtotal']->getValue() : $quote->getSubtotal(),
                    (int) $quote->getItemsQty()
                );

                if ($this->trackingHelper->isEventMessageQueueAvailable(self::EVENT, $storeId)) {
                    $this->publisher->publish(self::EVENT, $cartStatusEvent, $storeId);
                } else {
                    $this->sender->send(self::EVENT, $cartStatusEvent, $storeId);
                }
            }
        } catch (\Exception $e) {
            if (!$e instanceof ApiException) {
                $this->loggerHelper->getLogger()->error($e);
            }
        }
    }
}
