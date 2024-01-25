<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CustomeventRequestParams;
use Synerise\Integration\Helper\Cart;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\MessageQueue\Publisher\Event as EventPublisher;
use Synerise\Integration\SyneriseApi\Sender\Event as EventSender;

class CartStatus implements ObserverInterface
{
    public const EVENT = 'sales_quote_save_after';

    /**
     * @var CustomeventRequestParams
     */
    protected $previousParams = null;

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
            $quote = $observer->getQuote();
            $storeId = $quote->getStoreId();

            $totals = $quote->getTotals();

            if (!$this->trackingHelper->isEventTrackingAvailable(self::EVENT, $storeId)) {
                return;
            }

            if (!$this->trackingHelper->getClientUuid() && !$quote->getCustomerEmail()) {
                return;
            }

            $cartStatusEvent = null;

            if ($this->cartHelper->hasItemDataChanges($quote)) {
                $cartStatusEvent = $this->cartHelper->prepareCartStatusEvent(
                    $quote,
                    isset($totals['subtotal']) ? (double) $totals['subtotal']->getValue() : $quote->getSubtotal(),
                    (int) $quote->getItemsQty()
                );
            } elseif ($quote->dataHasChangedFor('reserved_order_id')) {
                $cartStatusEvent = $this->cartHelper->prepareCartStatusEvent($quote, 0, 0);
            }

            if ($cartStatusEvent) {
                if ($this->previousParams && $this->previousParams === $cartStatusEvent->getParams()) {
                    return;
                }

                if ($this->trackingHelper->isEventMessageQueueAvailable(self::EVENT, $storeId)) {
                    $this->publisher->publish(self::EVENT, $cartStatusEvent, $storeId);
                } else {
                    $this->sender->send(self::EVENT, $cartStatusEvent, $storeId);
                }
                $this->previousParams = $cartStatusEvent->getParams();
            }
        } catch (\Exception $e) {
            if (!$e instanceof ApiException) {
                $this->loggerHelper->getLogger()->error($e);
            }
        }
    }
}
