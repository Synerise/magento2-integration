<?php

namespace Synerise\Integration\Observer;

use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Synerise\Integration\Helper\Logger;
use \Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\MessageQueue\Publisher\Data\Item as Publisher;
use Synerise\Integration\SyneriseApi\Sender\Data\Product as Sender;

class CatalogProductSaveAfter implements ObserverInterface
{
    public const EVENT = 'catalog_product_save_after';

    /**
     * @var Synchronization
     */
    protected $synchronizationHelper;

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
     * @param Synchronization $synchronizationHelper
     * @param Logger $loggerHelper
     * @param Tracking $trackingHelper
     * @param Publisher $publisher
     */
    public function __construct(
        Synchronization $synchronizationHelper,
        Logger $loggerHelper,
        Tracking $trackingHelper,
        Publisher $publisher
    ) {
        $this->synchronizationHelper = $synchronizationHelper;
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
        if (!$this->trackingHelper->isEventTrackingAvailable(self::EVENT)) {
            return;
        }

        if (!$this->synchronizationHelper->isEnabledModel(Sender::MODEL)) {
            return;
        }

        try {
            $this->publishForEachStore($observer->getEvent()->getProduct());
        } catch (\Exception $e) {
            $this->loggerHelper->getLogger()->error($e);
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
        $enabledStores = $this->synchronizationHelper->getEnabledStores();
        $storeIds = $product->getStoreIds();
        foreach ($storeIds as $storeId) {
            if (in_array($storeId, $enabledStores)) {

                $this->publisher->publish(
                    Sender::MODEL,
                    (int) $product->getEntityId(),
                    (int) $storeId,
                    $this->synchronizationHelper->getWebsiteIdByStoreId($storeId)
                );
            }
        }
    }
}
