<?php

namespace Synerise\Integration\Observer;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use \Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\Helper\DataStorage;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\Model\Config\Source\Debug\Exclude;
use Synerise\Integration\MessageQueue\Publisher\Data\Item as Publisher;
use Synerise\Integration\SyneriseApi\Sender\Data\Product as Sender;

class ProductIsSalableChange implements ObserverInterface
{
    public const EVENT = 'product_is_salable_change';

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var DataStorage
     */
    protected $data;

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
        ProductRepositoryInterface $productRepository,
        DataStorage $data,
        Synchronization $synchronizationHelper,
        Tracking $trackingHelper,
        Publisher $publisher
    ) {
        $this->productRepository = $productRepository;
        $this->data = $data;
        $this->synchronizationHelper = $synchronizationHelper;
        $this->trackingHelper = $trackingHelper;
        $this->publisher = $publisher;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->trackingHelper->isEventTrackingEnabled(self::EVENT)) {
            return;
        }

        if (!$this->synchronizationHelper->isEnabledModel(Sender::MODEL)) {
            return;
        }

        $eventName = $observer->getEvent()->getName();

        try {
            $changedProducts = [];
            if ($eventName === 'sales_order_item_save_before') {
                $salesOrderItem = $observer->getData('item');
                $this->data->setData($salesOrderItem->getSku(), $salesOrderItem->getProduct());
            } elseif ($eventName === 'sales_order_item_save_after') {
                $salesOrderItem = $observer->getData('item');
                $product = $this->productRepository->get($salesOrderItem->getSku(), false, $salesOrderItem->getStoreId(), true);
                $productFromDataStorage = $this->data->getAndUnsetData($salesOrderItem->getSku());
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
                    foreach($changedProducts as $changedProduct) {
                        $this->publishForEachStore($changedProduct);
                    }
                } catch (\Exception $e) {
                    $this->trackingHelper->getLogger()->error($e);
                }
            }
        } catch (NoSuchEntityException $e) {
            if (!$this->trackingHelper->isExcludedFromLogging(Exclude::EXCEPTION_PRODUCT_NOT_FOUND)) {
                $this->trackingHelper->getLogger()->warning($e->getMessage());
            }
        } catch (\Exception $e) {
            $this->trackingHelper->getLogger()->error($e);
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
