<?php

namespace Synerise\Integration\Observer;

use Magento\CatalogInventory\Model\Stock\StockItemRepository;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\Model\Synchronization\Product as SyncProduct;

class StockStatusChange implements ObserverInterface
{
    const EVENT = 'sales_order_save_commit_after';

    /**
     * @var Tracking
     */
    protected $trackingHelper;

    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var StockItemRepository
     */
    private $stockItemRepository;

    public function __construct(
        LoggerInterface $logger,
        SyncProduct $syncProduct,
        Tracking $trackingHelper,
        StockItemRepository $stockItemRepository
    ) {
        $this->logger = $logger;
        $this->syncProduct = $syncProduct;
        $this->trackingHelper = $trackingHelper;
        $this->stockItemRepository = $stockItemRepository;
    }

    public function execute(Observer $observer)
    {
        if(!$this->trackingHelper->isEventTrackingEnabled(CatalogProductSaveAfter::EVENT)) {
            return;
        }

        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();
        if($order->getState() === \Magento\Sales\Model\Order::STATE_COMPLETE) {
            $items = $order->getItemsCollection();

            /** @var \Magento\Sales\Model\Order\Item $item */
            foreach($items as $item){
                /** @var \Magento\CatalogInventory\Model\Stock\Item $stockItem */
                $stockItem = $this->stockItemRepository->get($item->getProductId());
                if($stockItem->getManageStock() && $stockItem->getIsInStock() === false){
                    try {

                        $this->syncProduct->addItemsToQueue([
                            $item->getProduct()
                        ]);
                        $this->logger->info('Product '.$stockItem->getProductId().' added to cron queue');
                    } catch (\Exception $e) {
                        $this->logger->error('Failed to add product to cron queue', ['exception' => $e]);
                    }
                }
            }
        }
    }
}
