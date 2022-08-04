<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\InventoryConfiguration\Model\GetLegacyStockItem;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\Model\Synchronization\Product as SyncProduct;

class StockStatusChange implements ObserverInterface
{
    public const EVENT = 'stock_status_change';

    /**
     * @var Tracking
     */
    protected $trackingHelper;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var GetLegacyStockItem
     */
    protected $getLegacyStockItem;

    /**
     * @var SyncProduct
     */
    protected $syncProduct;

    public function __construct(
        LoggerInterface $logger,
        SyncProduct $syncProduct,
        Tracking $trackingHelper,
        GetLegacyStockItem $getLegacyStockItem
    ) {
        $this->logger = $logger;
        $this->syncProduct = $syncProduct;
        $this->trackingHelper = $trackingHelper;
        $this->getLegacyStockItem = $getLegacyStockItem;
    }


    public function execute(Observer $observer)
    {
        if (!$this->trackingHelper->isEventTrackingEnabled(self::EVENT)) {
            return;
        }

        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();

        if ($order->getState() === Order::STATE_COMPLETE) {
            $items = $order->getItemsCollection();
            foreach ($items as $item) {
                try {
                    $product = $item->getProduct();
                    $stockItem = $this->getLegacyStockItem->execute($item->getSku());
                    if(!$stockItem->getManageStock() || $stockItem->getBackorders()){
                        continue;
                    }

                    if($product->getQuantityAndStockStatus()['qty'] - $item->getQtyShipped() > 0){
                        continue;
                    }

                    $this->syncProduct->addItemsToQueue([
                        $item->getProduct()
                    ]);
                    $this->logger->info('Product ' . $item->getProductId() . ' added to cron queue');
                } catch (\Exception $e) {
                    $this->logger->error('Failed to add product to cron queue', ['exception' => $e]);
                }
            }
        }
    }
}