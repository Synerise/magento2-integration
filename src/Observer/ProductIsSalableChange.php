<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\ObserverInterface;

class ProductIsSalableChange implements ObserverInterface
{
    public const EVENT = 'product_is_salable_change';

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
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var \Synerise\Integration\Helper\DataStorage
     */
    protected $data;

    public function __construct(
        \Psr\Log\LoggerInterface                        $logger,
        \Synerise\Integration\Cron\Synchronization      $synchronization,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Synerise\Integration\Helper\DataStorage        $data
    ) {
        $this->logger = $logger;
        $this->synchronization = $synchronization;
        $this->productRepository = $productRepository;
        $this->data = $data;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $eventName = $observer->getEvent()->getName();

        $changedProducts = [];

        if ($eventName === 'sales_order_item_save_before') {
            $salesOrderItem = $observer->getData('item');
            $this->data->setData($salesOrderItem->getSku(), $salesOrderItem->getProduct());
        } elseif ($eventName === 'sales_order_item_save_after') {
            $salesOrderItem = $observer->getData('item');
            $product = $this->productRepository->get($salesOrderItem->getSku(), false, $salesOrderItem->getStoreId(), true);
            $productFromDataStorage = $this->data->getData($salesOrderItem->getSku());
            if ($productFromDataStorage &&
                $product->getData('is_salable') !== $productFromDataStorage->getData('is_salable')) {
                $changedProducts[] = $product;
            }
        } elseif ($eventName === 'sales_order_place_after') {
            $order = $observer->getData('order');
            $orderItems = $order->getAllItems();
            foreach ($orderItems as $orderItem) {
                $product = $orderItem->getProduct();
                $isSalable = $product->getIsSalable();
                if (!$isSalable) {
                    $changedProducts[] = $product;
                }
            }
        }

        if (!empty($changedProducts)) {
            try {
                $this->synchronization->addItemsToQueueByItemWebsiteIds(
                    'product',
                    $changedProducts
                );
            } catch (\Exception $e) {
                $this->logger->error('Failed to add products to cron queue', ['exception' => $e]);
            }
        }
    }
}
