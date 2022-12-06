<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\ObserverInterface;

class CartQtyUpdate implements ObserverInterface
{
    const EVENT = 'checkout_cart_update_items_after';

    protected $catalogHelper;
    protected $trackingHelper;
    protected $logger;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Synerise\Integration\Helper\Catalog $catalogHelper,
        \Synerise\Integration\Helper\Tracking $trackingHelper
    ) {
        $this->logger = $logger;
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

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getCart()->getQuote();
        $quote->collectTotals();

        if (!$this->trackingHelper->hasItemDataChanges($quote)) {
            // quote save won't be triggered, send event.
            $this->trackingHelper->sendCartStatusEvent(
                $this->catalogHelper->prepareProductsFromQuote($quote),
                (float) $quote->getSubtotal(),
                (int) $quote->getItemsQty(),
                $quote
            );
        }
    }
}
