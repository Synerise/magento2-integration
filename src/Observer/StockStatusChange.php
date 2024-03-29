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
use Magento\Store\Model\StoreManagerInterface;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\MessageQueue\Publisher\Data\Item as Publisher;
use Synerise\Integration\Model\Synchronization\Config;
use Synerise\Integration\SyneriseApi\Sender\Data\Product as Sender;

class StockStatusChange implements ObserverInterface
{
    public const EVENT = 'stock_status_change';

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

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
     * @var Config
     */
    protected $synchronization;

    /**
     * @var Tracking
     */
    protected $trackingHelper;

    /**
     * @var Publisher
     */
    protected $publisher;

    /**
     * @param StoreManagerInterface $storeManager
     * @param Manager $moduleManager
     * @param ObjectManagerInterface $objectManager
     * @param Logger $loggerHelper
     * @param Config $synchronization
     * @param Tracking $trackingHelper
     * @param Publisher $publisher
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Manager $moduleManager,
        ObjectManagerInterface $objectManager,
        Logger $loggerHelper,
        Config $synchronization,
        Tracking $trackingHelper,
        Publisher $publisher
    ) {
        $this->storeManager = $storeManager;
        $this->objectManager = $objectManager;
        $this->moduleManager = $moduleManager;
        $this->loggerHelper = $loggerHelper;
        $this->synchronization = $synchronization;
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
        if (!$this->moduleManager->isEnabled('Magento_InventoryCatalogApi')) {
            return;
        }

        if (!$this->synchronization->isModelEnabled(Sender::MODEL)) {
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
        $enabledStores = $this->synchronization->getConfiguredStores();
        $storeIds = $product->getStoreIds();
        foreach ($storeIds as $storeId) {
            if (!$this->trackingHelper->isEventTrackingAvailable(self::EVENT, $storeId)) {
                return;
            }

            if (in_array($storeId, $enabledStores)) {
                $this->publisher->publish(
                    Sender::MODEL,
                    $product->getEntityId(),
                    $product->getStoreId(),
                    $this->getWebsiteIdByStoreId($product->getStoreId())
                );
            }
        }
    }

    /**
     * Get website ID by store ID
     *
     * @param int $storeId
     * @return int
     * @throws NoSuchEntityException
     */
    public function getWebsiteIdByStoreId(int $storeId): int
    {
        return $this->storeManager->getStore($storeId)->getWebsiteId();
    }
}
