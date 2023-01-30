<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Synerise\ApiClient\Model\ClientaddedproducttocartRequest;

class CartAddProduct implements ObserverInterface
{
    const EVENT = 'checkout_cart_add_product_complete';

    protected $apiHelper;
    protected $trackingHelper;
    protected $logger;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Synerise\Integration\Helper\Api $apiHelper,
        \Synerise\Integration\Helper\Catalog $catalogHelper,
        \Synerise\Integration\Helper\Tracking $trackingHelper
    ) {
        $this->logger = $logger;
        $this->apiHelper = $apiHelper;
        $this->catalogHelper = $catalogHelper;
        $this->trackingHelper = $trackingHelper;
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

            $eventClientAction = new ClientaddedproducttocartRequest([
                'time' => $this->trackingHelper->getCurrentTime(),
                'label' => $this->trackingHelper->getEventLabel(self::EVENT),
                'client' => $client,
                'params' => $params
            ]);

            if ($this->apiHelper->isLiveRequestAsync()) {
                $this->apiHelper->getDefaultApiInstance()
                    ->clientAddedProductToCartAsync('4.4', $eventClientAction);
            } else {
                $this->apiHelper->getDefaultApiInstance()
                    ->clientAddedProductToCart('4.4', $eventClientAction);
            }
        } catch (\Exception $e) {
            $this->logger->error('Synerise Api request failed', ['exception' => $e]);
        }
    }
}
