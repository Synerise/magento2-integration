<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\Helper\Tracking\Context;
use Synerise\Integration\Helper\Tracking\Cookie;
use Synerise\Integration\MessageQueue\Publisher\Event as EventPublisher;
use Synerise\Integration\SyneriseApi\Mapper\CartAddRemove;
use Synerise\Integration\SyneriseApi\Sender\Event as EventSender;

class CartAddProduct implements ObserverInterface
{
    public const EVENT = 'checkout_cart_add_product_complete';

    /**
     * @var Context
     */
    protected $contextHelper;

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
     * @var CartAddRemove
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
     * @param CartAddRemove $mapper
     * @param Context $contextHelper
     * @param Cookie $cookieHelper
     * @param Logger $loggerHelper
     * @param Tracking $trackingHelper
     * @param EventPublisher $publisher
     * @param EventSender $sender
     */
    public function __construct(
        CartAddRemove $mapper,
        Context $contextHelper,
        Cookie $cookieHelper,
        Logger $loggerHelper,
        Tracking $trackingHelper,
        EventPublisher $publisher,
        EventSender $sender
    ) {
        $this->mapper = $mapper;
        $this->contextHelper = $contextHelper;
        $this->cookieHelper = $cookieHelper;
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
        if (!$this->contextHelper->isFrontend()) {
            return;
        }

        try {
            /** @var Quote\Item $quoteItem */
            $quoteItem = $observer->getQuoteItem();
            $storeId = $quoteItem->getStoreId();

            if (!$this->trackingHelper->isEventTrackingAvailable(self::EVENT, $storeId)) {
                return;
            }

            if ($quoteItem->getProduct()->getParentProductId()) {
                return;
            }

            $uuid = $this->trackingHelper->getClientUuid();
            if (!$uuid && !$quoteItem->getQuote()->getCustomerEmail()) {
                return;
            }

            $eventClientAction = $this->mapper->prepareRequest(
                self::EVENT,
                $quoteItem,
                $uuid,
                $this->cookieHelper->shouldIncludeSnrsParams() ? $this->cookieHelper->getSnrsParams() : []
            );

            if ($this->trackingHelper->isEventMessageQueueAvailable(self::EVENT, $storeId)) {
                $this->publisher->publish(self::EVENT, $eventClientAction, $storeId);
            } else {
                $this->sender->send(self::EVENT, $eventClientAction, $storeId);
            }
        } catch (\Exception $e) {
            if (!$e instanceof ApiException) {
                $this->loggerHelper->error($e);
            }
        }
    }
}
