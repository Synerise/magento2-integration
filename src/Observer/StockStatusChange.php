<?php

namespace Synerise\Integration\Observer;

use Magento\Catalog\Model\Product;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Module\Manager;
use Magento\InventoryConfiguration\Model\GetLegacyStockItem;
use Magento\Sales\Model\Order;
use \Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\Model\Synchronization\MessageQueue\Data\Single\Publisher;
use Synerise\Integration\Model\Synchronization\Sender\Product as Sender;

class StockStatusChange implements ObserverInterface
{
    const EVENT = 'stock_status_change';

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var Manager
     */
    protected $moduleManager;

    /**
     * @var Synchronization
     */
    protected $synchronizationHelper;

    /**
     * @var Tracking
     */
    protected $trackingHelper;

    /**
     * @var Publisher
     */
    protected $publisher;

    public function __construct(
        Manager $moduleManager,
        ObjectManagerInterface $objectManager,
        Synchronization $synchronizationHelper,
        Tracking $trackingHelper,
        Publisher $publisher
    ) {
        $this->objectManager = $objectManager;
        $this->moduleManager = $moduleManager;
        $this->synchronizationHelper = $synchronizationHelper;
        $this->trackingHelper = $trackingHelper;
        $this->publisher = $publisher;
    }

    public function execute(Observer $observer)
    {
        if (!$this->trackingHelper->isEventTrackingEnabled(self::EVENT)) {
            return;
        }

        if (!$this->moduleManager->isEnabled('Magento_InventoryCatalogApi')) {
            return;
        }

        /** @var GetLegacyStockItem $getLegacyStockItem */
        $getLegacyStockItem = $this->objectManager->get(GetLegacyStockItem::class);

        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();

        if ($order->getState() === Order::STATE_COMPLETE) {
            $items = $order->getItemsCollection();
            foreach ($items as $item) {
                try {
                    $product = $item->getProduct();
                    $stockItem = $getLegacyStockItem->execute($item->getSku());
                    if (!$stockItem->getManageStock() || $stockItem->getBackorders()) {
                        continue;
                    }

                    if ($product->getQuantityAndStockStatus()['qty'] - $item->getQtyShipped() > 0) {
                        continue;
                    }

                    $this->publishForEachStore($item->getProduct());
                } catch (\Exception $e) {
                    $this->trackingHelper->getLogger()->error($e);
                }
            }
        }
    }

    protected function publishForEachStore(Product $product)
    {
        $enabledStores = $this->synchronizationHelper->getEnabledStores();
        $storeIds = $product->getStoreIds();
        foreach ($storeIds as $storeId) {
            if (in_array($storeId, $enabledStores)) {
                $this->publisher->publish(
                    Sender::MODEL,
                    $product->getEntityId(),
                    $product->getStoreId(),
                    $this->synchronizationHelper->getWebsiteIdByStoreId($product->getStoreId())
                );
            }
        }
    }
}
