<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\ClientaddedproducttocartRequest;

class CartRemoveProduct implements ObserverInterface
{
    const EVENT = 'sales_quote_remove_item';

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
    protected $eventHelper;

    public function __construct(
        \Synerise\Integration\Helper\Api $apiHelper,
        \Synerise\Integration\Helper\Catalog $catalogHelper,
        \Synerise\Integration\Helper\Tracking $trackingHelper,
        \Synerise\Integration\Helper\Queue $queueHelper,
        \Synerise\Integration\Helper\Event $eventHelper
    ) {
        $this->apiHelper = $apiHelper;
        $this->catalogHelper = $catalogHelper;
        $this->trackingHelper = $trackingHelper;
        $this->queueHelper = $queueHelper;
        $this->eventHelper = $eventHelper;
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

            $eventClientAction = new ClientaddedproducttocartRequest([
                'time' => $this->trackingHelper->getCurrentTime(),
                'label' => $this->trackingHelper->getEventLabel(self::EVENT),
                'client' => $client,
                'params' => $params
            ]);

            if ($this->queueHelper->isQueueAvailable(self::EVENT)) {
                $this->queueHelper->publishEvent(self::EVENT, $eventClientAction, $storeId);
            } else {
                $this->eventHelper->sendEvent(self::EVENT, $eventClientAction, $storeId);
            }
        } catch (ApiException $e) {
        } catch (\Exception $e) {
            $this->trackingHelper->getLogger()->error($e);
        }
    }
}
