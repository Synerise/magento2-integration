<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\ObserverInterface;

class CartStatus implements ObserverInterface
{
    const EVENT = 'sales_quote_save_after';

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
        if (!$this->trackingHelper->isEventTrackingEnabled(self::EVENT)) {
            return;
        }

        if ($this->trackingHelper->isAdminStore()) {
            return;
        }

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getQuote();

        if ($this->trackingHelper->hasItemDataChanges($quote)) {
            $this->trackingHelper->sendCartStatusEvent(
                $this->catalogHelper->prepareProductsFromQuote($quote),
                (float) $quote->getSubtotal(),
                (int) $quote->getItemsQty(),
                $quote
            );
        } elseif ($quote->dataHasChangedFor('reserved_order_id')) {
            $this->trackingHelper->sendCartStatusEvent([], 0, 0, $quote);
        }
    }
}
