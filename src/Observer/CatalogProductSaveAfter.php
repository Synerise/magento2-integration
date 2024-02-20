<?php

namespace Synerise\Integration\Observer;

use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\MessageQueue\Publisher\Data\Item as Publisher;
use Synerise\Integration\Model\Synchronization\Config;
use Synerise\Integration\SyneriseApi\Sender\Data\Product as Sender;

class CatalogProductSaveAfter implements ObserverInterface
{
    public const EVENT = 'catalog_product_save_after';

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Config
     */
    protected $synchronization;

    /**
     * @var Logger
     */
    protected $loggerHelper;

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
     * @param Config $synchronization
     * @param Logger $loggerHelper
     * @param Tracking $trackingHelper
     * @param Publisher $publisher
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Config $synchronization,
        Logger $loggerHelper,
        Tracking $trackingHelper,
        Publisher $publisher
    ) {
        $this->storeManager = $storeManager;
        $this->synchronization = $synchronization;
        $this->loggerHelper = $loggerHelper;
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
        if (!$this->synchronization->isModelEnabled(Sender::MODEL)) {
            return;
        }

        try {
            $this->publishForEachStore($observer->getEvent()->getProduct());
        } catch (\Exception $e) {
            $this->loggerHelper->error($e);
        }
    }

    /**
     * Publish message for each store
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
            if (in_array($storeId, $enabledStores)) {

                if (!$this->trackingHelper->isEventTrackingAvailable(self::EVENT, $storeId)) {
                    return;
                }

                $this->publisher->publish(
                    Sender::MODEL,
                    (int) $product->getEntityId(),
                    (int) $storeId,
                    $this->getWebsiteIdByStoreId($storeId)
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
