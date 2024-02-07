<?php

namespace Synerise\Integration\Observer;

use Magento\Catalog\Model\ResourceModel\Product\Website\Link;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\Store\Model\StoreManagerInterface;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\MessageQueue\Publisher\Data\Item as Publisher;
use Synerise\Integration\Model\Config\Source\Debug\Exclude;
use Synerise\Integration\Model\Synchronization\Config as SynchronizationConfig;
use Synerise\Integration\Model\Tracking\Config as TrackingConfig;
use Synerise\Integration\Model\Tracking\ConfigFactory as TrackingConfigFactory;
use Synerise\Integration\SyneriseApi\Sender\Data\Product as Sender;

class UpdateIndexSalabilityStatus
{
    public const EVENT = 'product_is_salable_change';

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Link
     */
    private $productWebsiteLink;

    /**
     * @var GetProductIdsBySkusInterface
     */
    private $getProductIdsBySkus;

    /**
     * @var TrackingConfigFactory
     */
    private $trackingConfigFactory;

    /**
     * @var SynchronizationConfig
     */
    private $synchronizationConfig;

    /**
     * @var Publisher
     */
    private $publisher;

    /**
     * @var Logger
     */
    private $loggerHelper;

    /**
     * @var TrackingConfig[]
     */
    private $config;

    /**
     * @var int[]
     */
    private $storeIds;

    /**
     * @param StoreManagerInterface $storeManager
     * @param Link $productWebsiteLink
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @param TrackingConfigFactory $trackingConfigFactory
     * @param SynchronizationConfig $synchronizationConfig
     * @param Publisher $publisher
     * @param Logger $loggerHelper
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Link $productWebsiteLink,
        GetProductIdsBySkusInterface $getProductIdsBySkus,
        TrackingConfigFactory $trackingConfigFactory,
        SynchronizationConfig $synchronizationConfig,
        Publisher $publisher,
        Logger $loggerHelper
    ) {
        $this->storeManager = $storeManager;
        $this->productWebsiteLink = $productWebsiteLink;
        $this->getProductIdsBySkus = $getProductIdsBySkus;
        $this->trackingConfigFactory = $trackingConfigFactory;
        $this->synchronizationConfig = $synchronizationConfig;
        $this->publisher = $publisher;
        $this->loggerHelper = $loggerHelper;
    }

    /**
     * Add Products to update queue
     *
     * @param UpdateIndexSalabilityStatus $subject
     * @param array $skusAffected
     *
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(UpdateIndexSalabilityStatus $subject, array $skusAffected)
    {
        if (!$this->synchronizationConfig->isModelEnabled(Sender::MODEL)) {
            return $skusAffected;
        }

        if ($skus = array_keys($skusAffected)) {
            try {
                foreach ($this->getProductIdsBySkus->execute($skus) as $productId) {
                    $this->publishForEachStore($productId);
                }
            } catch (NoSuchEntityException $e) {
                if (!$this->loggerHelper->isExcludedFromLogging(Exclude::EXCEPTION_PRODUCT_NOT_FOUND)) {
                    $this->loggerHelper->getLogger()->warning($e->getMessage());
                }
            }
        }

        return $skusAffected;
    }

    /**
     * Publish product on synchronization queue for each store
     *
     * @param int $productId
     * @return void
     */
    protected function publishForEachStore(int $productId)
    {
        foreach ($this->productWebsiteLink->getWebsiteIdsByProductId($productId) as $websiteId) {
            try {
                foreach ($this->getStoreIds($websiteId) as $storeId) {
                    if (!$this->getTrackingConfig($storeId)->isEventTrackingEnabled(self::EVENT)) {
                        return;
                    }

                    if ($this->synchronizationConfig->isStoreConfigured($storeId)) {
                        $this->publisher->publish(Sender::MODEL, $productId, $storeId, $websiteId);
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
    }

    /**
     * Get all sore ids where product is presented
     *
     * @param int $websiteId
     * @return array
     * @throws LocalizedException|NoSuchEntityException
     */
    public function getStoreIds(int $websiteId): array
    {
        if (!isset($this->storeIds[$websiteId])) {
            $this->storeIds[$websiteId] = $this->storeManager->getWebsite($websiteId)->getStoreIds();
        }
        return $this->storeIds[$websiteId];
    }

    /**
     * Get tracking config by store ID
     *
     * @param int $storeId
     * @return TrackingConfig
     */
    public function getTrackingConfig(int $storeId): TrackingConfig
    {
        if (!isset($this->config[$storeId])) {
            $this->config[$storeId] = $this->trackingConfigFactory->create($storeId);
        }

        return $this->config[$storeId];
    }
}
