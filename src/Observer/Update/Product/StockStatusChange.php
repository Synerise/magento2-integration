<?php

namespace Synerise\Integration\Observer\Update\Product;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\InventoryConfiguration\Model\GetLegacyStockItem;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Helper\Synchronization\Sender\Product as ProductSender;
use Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\Observer\AbstractObserver;

class StockStatusChange  extends AbstractObserver implements ObserverInterface
{
    public const EVENT = 'stock_status_change';

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var GetLegacyStockItem
     */
    protected $getLegacyStockItem;

    /**
     * @var Synchronization
     */
    private $synchronization;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        GetLegacyStockItem $getLegacyStockItem,
        Synchronization $synchronization
    ) {
        $this->logger = $logger;
        $this->getLegacyStockItem = $getLegacyStockItem;

        $this->synchronization = $synchronization;

        parent::__construct($scopeConfig, $logger);
    }


    public function execute(Observer $observer)
    {
        if (!$this->isEventTrackingEnabled(self::EVENT)) {
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

                    $this->synchronization->addItemsToQueuePerStore(
                        [$product],
                        ProductSender::MODEL,
                        ProductSender::ENTITY_ID
                    );
                    $this->logger->info('Product ' . $item->getProductId() . ' added to cron queue');
                } catch (\Exception $e) {
                    $this->logger->error('Failed to add product to cron queue', ['exception' => $e]);
                }
            }
       }
    }
}
