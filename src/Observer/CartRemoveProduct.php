<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\ClientaddedproducttocartRequest;
use Synerise\Integration\Helper\Cart;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\MessageQueue\Publisher\Event as EventPublisher;
use Synerise\Integration\SyneriseApi\Sender\Event as EventSender;

class CartRemoveProduct implements ObserverInterface
{
    public const EVENT = 'sales_quote_remove_item';

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
            /** @var Quote\Item $quoteItem */
            $quoteItem = $observer->getQuoteItem();
            $storeId = $quoteItem->getStoreId();

            if (!$this->trackingHelper->isEventTrackingAvailable(self::EVENT, $storeId)) {
                return;
            }

            $product = $quoteItem->getProduct();

            if ($product->getParentProductId()) {
                return;
            }

            if (!$this->trackingHelper->getClientUuid() && !$quoteItem->getQuote()->getCustomerEmail()) {
                return;
            }

            $client = $this->trackingHelper->prepareClientDataFromQuote($quoteItem->getQuote());
            $params = array_merge(
                $this->trackingHelper->prepareContextParams(),
                $this->cartHelper->prepareParamsFromQuoteProduct($product)
            );

            $eventClientAction = new ClientaddedproducttocartRequest([
                'event_salt' => $this->trackingHelper->generateEventSalt(),
                'time' => $this->trackingHelper->getContext()->getCurrentTime(),
                'label' => $this->trackingHelper->getEventLabel(self::EVENT),
                'client' => $client,
                'params' => $params
            ]);

            if ($this->trackingHelper->isEventMessageQueueAvailable(self::EVENT, $storeId)) {
                $this->publisher->publish(self::EVENT, $eventClientAction, $storeId);
            } else {
                $this->sender->send(self::EVENT, $eventClientAction, $storeId);
            }
        } catch (\Exception $e) {
            if (!$e instanceof ApiException) {
                $this->loggerHelper->getLogger()->error($e);
            }
        }
    }
}
