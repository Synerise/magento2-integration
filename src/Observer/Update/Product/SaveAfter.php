<?php

namespace Synerise\Integration\Observer\Update\Product;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Helper\Synchronization\Sender\Product as ProductSender;
use Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\Observer\AbstractObserver;

class SaveAfter  extends AbstractObserver implements ObserverInterface
{
    const EVENT = 'catalog_product_save_after';

    /**
     * @var Synchronization
     */
    protected $synchronization;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        Synchronization $synchronization
    ) {
        $this->synchronization = $synchronization;

        parent::__construct($scopeConfig, $logger);
    }

    public function execute(Observer $observer)
    {
        if (!$this->isEventTrackingEnabled(self::EVENT)) {
            return;
        }

        /** @var Product $product */
        $product = $observer->getEvent()->getProduct();

        try {
            $this->synchronization->addItemsToQueuePerStore(
                [$product],
                ProductSender::MODEL,
                ProductSender::ENTITY_ID
            );
        } catch (\Exception $e) {
            $this->logger->error('Failed to add product to cron queue', ['exception' => $e]);
        }
    }
}
