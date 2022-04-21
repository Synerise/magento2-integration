<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\ObserverInterface;

class StockStatusChange implements ObserverInterface
{
    const EVENT = 'sales_order_save_commit_after';

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
    /**
     * @var \Magento\CatalogInventory\Model\Stock\StockItemRepository
     */
    private $stockItemRepository;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Synerise\Integration\Cron\Synchronization $synchronization,
        \Synerise\Integration\Helper\Tracking $trackingHelper,
        \Magento\CatalogInventory\Model\Stock\StockItemRepository $stockItemRepository
    ) {
        $this->logger = $logger;
        $this->synchronization = $synchronization;
        $this->trackingHelper = $trackingHelper;
        $this->stockItemRepository = $stockItemRepository;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
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
                        $this->synchronization->addItemToQueueByWebsiteIds(
                            'product',
                            [$stockItem->getWebsiteId()],
                            $stockItem->getProductId()
                        );
                        $this->logger->info('Product '.$stockItem->getProductId().' added to cron queue');
                    } catch (\Exception $e) {
                        $this->logger->error('Failed to add product to cron queue', ['exception' => $e]);
                    }
                }
            }
        }
    }
}
