<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Module\Manager;
use Magento\InventoryConfiguration\Model\GetLegacyStockItem;
use Magento\Sales\Model\Order;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\Model\Synchronization\Product as SyncProduct;

class StockStatusChange implements ObserverInterface
{
    const EVENT = 'stock_status_change';

    /**
     * @var Tracking
     */
    protected $trackingHelper;

    /**
     * @var SyncProduct
     */
    protected $syncProduct;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var Manager
     */
    protected $moduleManager;

    public function __construct(
        SyncProduct $syncProduct,
        Tracking $trackingHelper,
        Manager $moduleManager,
        ObjectManagerInterface $objectManager
    ) {
        $this->syncProduct = $syncProduct;
        $this->trackingHelper = $trackingHelper;
        $this->objectManager = $objectManager;
        $this->moduleManager = $moduleManager;
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

                    $this->syncProduct->addItemsToQueue([
                        $item->getProduct()
                    ]);
                } catch (\Exception $e) {
                    $this->trackingHelper->getLogger()->error($e);
                }
            }
        }
    }
}
