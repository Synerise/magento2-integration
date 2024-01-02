<?php

namespace Synerise\Integration\Observer;

use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Module\Manager;
use Magento\InventoryConfiguration\Model\GetLegacyStockItem;
use Magento\Sales\Model\Order;
use Synerise\Integration\Helper\Logger;
use \Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\MessageQueue\Publisher\Data\Item as Publisher;
use Synerise\Integration\SyneriseApi\Sender\Data\Product as Sender;

class StockStatusChange implements ObserverInterface
{
    public const EVENT = 'stock_status_change';

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var Manager
     */
    protected $moduleManager;

    /**
     * @var Logger
     */
    protected $loggerHelper;

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

    /**
     * @param Manager $moduleManager
     * @param ObjectManagerInterface $objectManager
     * @param Logger $loggerHelper
     * @param Synchronization $synchronizationHelper
     * @param Tracking $trackingHelper
     * @param Publisher $publisher
     */
    public function __construct(
        Manager $moduleManager,
        ObjectManagerInterface $objectManager,
        Logger $loggerHelper,
        Synchronization $synchronizationHelper,
        Tracking $trackingHelper,
        Publisher $publisher
    ) {
        $this->objectManager = $objectManager;
        $this->moduleManager = $moduleManager;
        $this->loggerHelper = $loggerHelper;
        $this->synchronizationHelper = $synchronizationHelper;
        $this->trackingHelper = $trackingHelper;
        $this->publisher = $publisher;
    }

    /**
     * Execute
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if (!$this->trackingHelper->isEventTrackingAvailable(self::EVENT)) {
            return;
        }

        if (!$this->moduleManager->isEnabled('Magento_InventoryCatalogApi')) {
            return;
        }

        if (!$this->synchronizationHelper->isEnabledModel(Sender::MODEL)) {
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
                    $this->loggerHelper->getLogger()->error($e);
                }
            }
        }
    }

    /**
     * Publish for each store
     *
     * @param Product $product
     * @return void
     * @throws NoSuchEntityException
     */
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
