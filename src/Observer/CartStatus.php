<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\ObserverInterface;
use function Webmozart\Assert\Tests\StaticAnalysis\resource;

class CartStatus implements ObserverInterface
{
    const EVENT = 'sales_quote_save_after';

    protected $apiHelper;
    protected $catalogHelper;
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
        if(!$this->trackingHelper->isEventTrackingEnabled(self::EVENT)) {
            return;
        }

        if(!$this->trackingHelper->getClientUuid()) {
            return;
        }

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getQuote();

        if(!$this->shouldTriggerEventForQuote($quote)) {
            return;
        }

        $items = $quote->getAllVisibleItems();

        $products = [];
        if(is_array($items)) {
            foreach($items as $item) {
                $products[] = $this->catalogHelper->prepareParamsfromQuoteItemForStatus($item);
            }
        }

        $customEventRequest = new \Synerise\ApiClient\Model\CustomeventRequest([
            'time' => $this->trackingHelper->getCurrentTime(),
            'action' => 'cart.status',
            'label' => 'CartStatus',
            'client' => [
                "uuid" => $this->trackingHelper->getClientUuid(),
            ],
            'params' => [
                'products' => $products,
                'totalAmount' => $quote->getGrandTotal(),
                'totalQuantity' => $quote->getItemsQty()
            ]
        ]);

        try {
            $this->apiHelper->getDefaultApiInstance()
                ->customEvent('4.4', $customEventRequest);

        } catch (\Exception $e) {
            $this->logger->error('Synerise Api request failed', ['exception' => $e]);
        }
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @return bool
     */
    protected function shouldTriggerEventForQuote(\Magento\Quote\Model\Quote $quote)
    {
        if($quote->isObjectNew()) {
            return true;
        }

        if(!$quote->getIsActive()) {
            return false;
        }

        if($quote->getOrigData('grand_total') == $quote->getGrandTotal()) {
            return false;
        }

        if($quote->getOrigData('items_qty') == $quote->getItemsQty()) {
            return false;
        }

        return true;
    }
}
