<?php

namespace Synerise\Integration\Observer;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\Model\Config\Source\Debug\Exclude;
use Synerise\Integration\MessageQueue\Publisher\Data\Item as Publisher;
use Synerise\Integration\Model\Synchronization\Config;
use Synerise\Integration\SyneriseApi\Sender\Data\Product as Sender;

class ProductIsSalableChange implements ObserverInterface
{
    public const EVENT = 'product_is_salable_change';

    /**
     * @var mixed
     */
    protected $products;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

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
     * @param ProductRepositoryInterface $productRepository
     * @param Logger $loggerHelper
     * @param Config $synchronization
     * @param Tracking $trackingHelper
     * @param Publisher $publisher
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ProductRepositoryInterface $productRepository,
        Logger $loggerHelper,
        Config $synchronization,
        Tracking $trackingHelper,
        Publisher $publisher
    ) {
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->loggerHelper = $loggerHelper;
        $this->synchronization = $synchronization;
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
        if (!$this->synchronization->isModelEnabled(Sender::MODEL)) {
            return;
        }

        $eventName = $observer->getEvent()->getName();

        try {
            $changedProducts = [];

            if ($eventName === 'sales_order_item_save_before') {
                $item = $observer->getData('item');
                $this->products[$item->getSku()] = $item->getProduct();
            } elseif ($eventName === 'sales_order_item_save_after') {
                $item = $observer->getData('item');
                if ($this->isSalableChanged($item->getSku(), $item->getStoreId())) {
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
