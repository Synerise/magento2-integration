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
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Synerise\Integration\Helper\Api
     */
    protected $apiHelper;

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
    protected $eventsHelper;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Synerise\Integration\Helper\Api $apiHelper,
        \Synerise\Integration\Helper\Catalog $catalogHelper,
        \Synerise\Integration\Helper\Tracking $trackingHelper,
        \Synerise\Integration\Helper\Queue $queueHelper,
        \Synerise\Integration\Helper\Event $eventsHelper
    ) {
        $this->logger = $logger;
        $this->apiHelper = $apiHelper;
        $this->catalogHelper = $catalogHelper;
        $this->trackingHelper = $trackingHelper;
        $this->queueHelper = $queueHelper;
        $this->eventsHelper = $eventsHelper;
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
            $params = $this->catalogHelper->prepareParamsFromQuoteProduct($product);

            $params["source"] = $this->trackingHelper->getSource();
            $params["applicationName"] = $this->trackingHelper->getApplicationName();
            $params["storeId"] = $this->trackingHelper->getStoreId();
            $params["storeUrl"] = $this->trackingHelper->getStoreBaseUrl();

            if($this->trackingHelper->shouldIncludeParams($this->trackingHelper->getStoreId()) && $this->trackingHelper->getCookieParams()) {
                $params['_snrs_p'] = $this->trackingHelper->getCookieParams();
            }

            $eventClientAction = new ClientaddedproducttocartRequest([
                'time' => $this->trackingHelper->getCurrentTime(),
                'label' => $this->trackingHelper->getEventLabel(self::EVENT),
                'client' => $client,
                'params' => $params
            ]);

            if ($this->queueHelper->isQueueAvailable()) {
                $this->queueHelper->publishEvent(self::EVENT, $eventClientAction, $storeId);
            } else {
                $this->eventsHelper->sendEvent(self::EVENT, $eventClientAction, $storeId);
            }
        } catch (ApiException $e) {
        } catch (\Exception $e) {
            $this->logger->error('Synerise Error', ['exception' => $e]);
        }
    }
}
