<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\ObserverInterface;

class CartRemoveProduct implements ObserverInterface
{
    const EVENT = 'sales_quote_remove_item';

    protected $apiHelper;
    protected $trackingHelper;
    protected $logger;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Synerise\Integration\Helper\Api $apiHelper,
        \Synerise\Integration\Helper\Tracking $trackingHelper
    ) {
        $this->logger = $logger;
        $this->apiHelper = $apiHelper;
        $this->trackingHelper = $trackingHelper;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if(!$this->trackingHelper->isEventTrackingEnabled(self::EVENT)) {
            return;
        }

        $eventClientAction = new \Synerise\ApiClient\Model\ClientaddedproducttocartRequest([
            'time' => $this->trackingHelper->getCurrentTime(),
            'label' => $this->trackingHelper->getEventLabel(self::EVENT),
            'client' => [
                "uuid" => $this->trackingHelper->getClientUuid(),
            ],
            'params' => $this->trackingHelper->prepareParamsfromQuoteProduct(
                $observer->getQuoteItem()->getProduct()
            )
        ]);

        try {
            $this->apiHelper->getDefaultApiInstance()
                ->clientRemovedProductFromCart('4.4', $eventClientAction);

        } catch (\Exception $e) {
            $this->logger->error('Synerise Api request failed', ['exception' => $e]);
        }
    }
}