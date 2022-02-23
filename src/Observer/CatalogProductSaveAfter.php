<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\ObserverInterface;

class CatalogProductSaveAfter implements ObserverInterface
{
    const EVENT = 'catalog_product_save_after';

    /**
     * @var \Synerise\Integration\Cron\Synchronization
     */
    protected $synchronization;

    /**
     * @var \Synerise\Integration\Helper\Tracking
     */
    protected $trackingHelper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Synerise\Integration\Cron\Synchronization $synchronization,
        \Synerise\Integration\Helper\Tracking $trackingHelper
    ) {
        $this->logger = $logger;
        $this->synchronization = $synchronization;
        $this->trackingHelper = $trackingHelper;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->trackingHelper->isEventTrackingEnabled(self::EVENT)) {
            return;
        }

        $product = $observer->getEvent()->getProduct();

        try {
            $this->synchronization->addItemToQueueByWebsiteIds(
                'product',
                $product->getWebsiteIds(),
                $product->getId()
            );
        } catch (\Exception $e) {
            $this->logger->error('Failed to add product to cron queue', ['exception' => $e]);
        }
    }
}
