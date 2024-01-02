<?php

namespace Synerise\Integration\Observer;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Synerise\Integration\Helper\Logger;
use \Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\Model\Config\Source\Debug\Exclude;
use Synerise\Integration\MessageQueue\Publisher\Data\Item as Publisher;
use Synerise\Integration\SyneriseApi\Sender\Data\Product as Sender;

class ProductIsSalableChange implements ObserverInterface
{
    public const EVENT = 'product_is_salable_change';

    /**
     * @var mixed
     */
    protected $products;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

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
     * @param ProductRepositoryInterface $productRepository
     * @param Logger $loggerHelper
     * @param Synchronization $synchronizationHelper
     * @param Tracking $trackingHelper
     * @param Publisher $publisher
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        Logger $loggerHelper,
        Synchronization $synchronizationHelper,
        Tracking $trackingHelper,
        Publisher $publisher
    ) {
        $this->productRepository = $productRepository;
        $this->loggerHelper = $loggerHelper;
        $this->synchronizationHelper = $synchronizationHelper;
        $this->trackingHelper = $trackingHelper;
        $this->publisher = $publisher;
    }

    /**
     * Observe potential saleability change
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if (!$this->trackingHelper->isEventTrackingAvailable(self::EVENT)) {
            return;
        }

        if (!$this->synchronizationHelper->isEnabledModel(Sender::MODEL)) {
            return;
        }

        $eventName = $observer->getEvent()->getName();

        try {
            $item = $observer->getData('item');
            $sku = $item->getSku();
            $changedProducts = [];

            if ($eventName === 'sales_order_item_save_before') {
                $this->products[$sku] = $item->getProduct();
            } elseif ($eventName === 'sales_order_item_save_after') {
                if ($this->isSalableChanged($sku, $item->getStoreId())) {
                    $changedProducts[] = $item->getProduct();
                }
            } elseif ($eventName === 'sales_order_place_after') {
                $order = $observer->getData('order');
                foreach ($order->getAllItems() as $item) {
                    $product = $item->getProduct();
                    if (!$product->getIsSalable()) {
                        $changedProducts[] = $item->getProduct();
                    }
                }
            }

            if (!empty($changedProducts)) {
                try {
                    foreach ($changedProducts as $changedProduct) {
                        $this->publishForEachStore($changedProduct);
                    }
                } catch (\Exception $e) {
                    $this->loggerHelper->getLogger()->error($e);
                }
            }
        } catch (NoSuchEntityException $e) {
            if (!$this->loggerHelper->isExcludedFromLogging(Exclude::EXCEPTION_PRODUCT_NOT_FOUND)) {
                $this->loggerHelper->getLogger()->warning($e->getMessage());
            }
        } catch (\Exception $e) {
            $this->loggerHelper->getLogger()->error($e);
        }
    }

    /**
     * Compare product is_salable attribute before and after order was saved
     *
     * @param string $sku
     * @param int $storeId
     * @return bool
     * @throws NoSuchEntityException
     */
    protected function isSalableChanged(string $sku, int $storeId): bool
    {
        if (!isset($this->products[$sku])) {
            return false;
        }

        $productBeforeSave = $this->products[$sku];
        $productAfterSave = $this->productRepository->get($sku, false, $storeId, true);
        unset($this->products[$sku]);

        return $productAfterSave->getData('is_salable') !== $productBeforeSave->getData('is_salable');
    }

    /**
     * Publish product on synchronization queue for each store
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
