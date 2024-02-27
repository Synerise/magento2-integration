<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\Helper\Tracking\Cookie;
use Synerise\Integration\SyneriseApi\Mapper\CartStatus as Mapper;
use Synerise\Integration\MessageQueue\Publisher\Event as EventPublisher;
use Synerise\Integration\SyneriseApi\Sender\Event as EventSender;

class CartQtyUpdate implements ObserverInterface
{
    public const EVENT = 'checkout_cart_update_items_after';

    /**
     * @var Cookie
     */
    protected $cookieHelper;

    /**
     * @var Logger
     */
    protected $loggerHelper;

    /**
     * @var Tracking
     */
    protected $trackingHelper;

    /**
     * @var Mapper
     */
    protected $mapper;

    /**
     * @var EventPublisher
     */
    protected $publisher;

    /**
     * @var EventSender
     */
    protected $sender;

    /**
     * @param Cookie $cookieHelper
     * @param Logger $loggerHelper
     * @param Tracking $trackingHelper
     * @param Mapper $mapper
     * @param EventPublisher $publisher
     * @param EventSender $sender
     */
    public function __construct(
        Cookie $cookieHelper,
        Logger $loggerHelper,
        Tracking $trackingHelper,
        Mapper $mapper,
        EventPublisher $publisher,
        EventSender $sender
    ) {
        $this->cookieHelper = $cookieHelper;
        $this->loggerHelper = $loggerHelper;
        $this->trackingHelper = $trackingHelper;
        $this->mapper = $mapper;
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

            $uuid = $this->trackingHelper->getClientUuid();
            if (!$uuid && !$quote->getCustomerEmail()) {
                return;
            }

            $quote->collectTotals();

            if (!$this->hasItemDataChanges($quote)) {
                // quote save won't be triggered, send event.
                $cartStatusEvent = $this->mapper->prepareRequest(
                    $quote,
                    $uuid,
                    $this->cookieHelper->shouldIncludeSnrsParams() ? $this->cookieHelper->getSnrsParams() : []
                );

                if ($this->trackingHelper->isEventMessageQueueAvailable(self::EVENT, $storeId)) {
                    $this->publisher->publish(self::EVENT, $cartStatusEvent, $storeId);
                } else {
                    $this->sender->send(self::EVENT, $cartStatusEvent, $storeId);
                }
            }
        } catch (\Exception $e) {
            if (!$e instanceof ApiException) {
                $this->loggerHelper->error($e);
            }
        }
    }

    /**
     * Check if cart was actually updated
     *
     * @param Quote $quote
     * @return bool
     */
    public function hasItemDataChanges(Quote $quote): bool
    {
        return ($quote->dataHasChangedFor('subtotal') || $quote->dataHasChangedFor('items_qty'));
    }
}
