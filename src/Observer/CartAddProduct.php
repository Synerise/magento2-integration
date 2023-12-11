<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\ClientaddedproducttocartRequest;

class CartAddProduct implements ObserverInterface
{
    const EVENT = 'checkout_cart_add_product_complete';

    /**
     * @var \Synerise\Integration\Helper\Cart
     */
    protected $cartHelper;

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

    public function __construct(
        \Synerise\Integration\Helper\Cart $cartHelper,
        \Synerise\Integration\Helper\Tracking $trackingHelper,
        \Synerise\Integration\MessageQueue\Publisher\Event $publisher,
        \Synerise\Integration\SyneriseApi\Sender\Event $sender
    ) {
        $this->cartHelper = $cartHelper;
        $this->trackingHelper = $trackingHelper;
        $this->publisher = $publisher;
        $this->sender = $sender;
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
            /** @var Quote\Item $quoteItem */
            $quoteItem = $observer->getQuoteItem();
            $storeId = $quoteItem->getStoreId();
            $product = $quoteItem->getProduct();

            if ($product->getParentProductId()) {
                return;
            }

            if (!$this->trackingHelper->getClientUuid() && !$quoteItem->getQuote()->getCustomerEmail()) {
                return;
            }

            $client = $this->trackingHelper->prepareClientDataFromQuote($quoteItem->getQuote());
            $params = $this->cartHelper->prepareParamsFromQuoteProduct($product);

            $params["source"] = $this->trackingHelper->getSource();
            $params["applicationName"] = $this->trackingHelper->getApplicationName();
            $params["storeId"] = $this->trackingHelper->getStoreId();
            $params["storeUrl"] = $this->trackingHelper->getStoreBaseUrl();

            if($this->trackingHelper->shouldIncludeParams($this->trackingHelper->getStoreId()) && $this->trackingHelper->getCookieParams()) {
                $params['snrs_params'] = $this->trackingHelper->getCookieParams();
            }

            $eventClientAction = new ClientaddedproducttocartRequest([
                'event_salt' => $this->trackingHelper->generateEventSalt(),
                'time' => $this->trackingHelper->getCurrentTime(),
                'label' => $this->trackingHelper->getEventLabel(self::EVENT),
                'client' => $client,
                'params' => $params
            ]);

            if ($this->trackingHelper->isQueueAvailable(self::EVENT, $storeId)) {
                $this->publisher->publish(self::EVENT, $eventClientAction, $storeId);
            } else {
                $this->sender->send(self::EVENT, $eventClientAction, $storeId);
            }
        } catch (\Exception $e) {
            if(!$e instanceof ApiException) {
                $this->trackingHelper->getLogger()->error($e);
            }
        }
    }
}
