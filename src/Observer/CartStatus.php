<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\ObserverInterface;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CustomeventRequestParams;

class CartStatus implements ObserverInterface
{
    const EVENT = 'sales_quote_save_after';

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
     * @var \Synerise\Integration\SyneriseApi\Sender\Event
     */
    protected $sender;

    /**
     * @var \Synerise\Integration\Helper\Cart
     */
    protected $cartHelper;

    /**
     * @var CustomeventRequestParams
     */
    protected $previousParams = null;

    public function __construct(
        \Synerise\Integration\Helper\Catalog $catalogHelper,
        \Synerise\Integration\Helper\Tracking $trackingHelper,
        \Synerise\Integration\MessageQueue\Publisher\Event $publisher,
        \Synerise\Integration\SyneriseApi\Sender\Event $sender,
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
            $quote = $observer->getQuote();
            $storeId = $quote->getStoreId();

            if (!$this->trackingHelper->getClientUuid() && !$quote->getCustomerEmail()) {
                return;
            }

            $cartStatusEvent = null;

            if ($this->cartHelper->hasItemDataChanges($quote)) {
                $cartStatusEvent = $this->cartHelper->prepareCartStatusEvent($quote, (float) $quote->getSubtotal(), (int) $quote->getItemsQty());
            } elseif ($quote->dataHasChangedFor('reserved_order_id')) {
                $cartStatusEvent = $this->cartHelper->prepareCartStatusEvent($quote, 0, 0);
            }

            if ($cartStatusEvent) {
                if ($this->previousParams && $this->previousParams === $cartStatusEvent->getParams()) {
                    return;
                }

                if ($this->trackingHelper->isQueueAvailable(self::EVENT, $storeId)) {
                    $this->publisher->publish(self::EVENT, $cartStatusEvent, $storeId);
                } else {
                    $this->sender->send(self::EVENT, $cartStatusEvent, $storeId);
                }
                $this->previousParams = $cartStatusEvent->getParams();
            }
        } catch (\Exception $e) {
            if(!$e instanceof ApiException) {
                $this->trackingHelper->getLogger()->error($e);
            }
        }
    }
}
