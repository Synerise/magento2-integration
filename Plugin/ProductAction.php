<?php

namespace Synerise\Integration\Plugin;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\Product\Action;
use Magento\Catalog\Model\ResourceModel\Product\Website\Link;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\MessageQueue\Publisher\Data\Batch as BatchPublisher;
use Synerise\Integration\MessageQueue\Publisher\Event as EventPublisher;
use Synerise\Integration\Model\Synchronization\Config as SynchronizationConfig;
use Synerise\Integration\Model\Tracking\ConfigFactory as TrackingConfigFactory;
use Synerise\Integration\Observer\Data\ProductDelete;
use Synerise\Integration\SyneriseApi\Mapper\Data\ProductCRUD;
use Synerise\Integration\SyneriseApi\Sender\Data\Product as ProductSender;

class ProductAction
{
    public const EVENT = 'catalog_product_action';

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var Link
     */
    protected $productWebsiteLink;

    /**
     * @var Logger
     */
    protected $loggerHelper;

    /**
     * @var TrackingConfigFactory
     */
    protected $trackingConfigFactory;

    /**
     * @var SynchronizationConfig
     */
    protected $synchronizationConfig;

    /**
     * @var BatchPublisher
     */
    protected $batchPublisher;

    /**
     * @var EventPublisher
     */
    protected $eventPublisher;

    /**
     * @var ProductCRUD
     */
    protected $productCRUD;
    /**
     * @var ProductSender
     */
    protected $productSender;

    /**
     * @var int[]
     */
    protected $storeIds;

    public function __construct(
        StoreManagerInterface $storeManager,
        CollectionFactory $productCollectionFactory,
        Link $productWebsiteLink,
        Logger $loggerHelper,
        TrackingConfigFactory $trackingConfigFactory,
        SynchronizationConfig $synchronizationConfig,
        BatchPublisher $batchPublisher,
        EventPublisher $eventPublisher,
        ProductCRUD $productCRUD,
        ProductSender $productSender
    ) {
        $this->storeManager = $storeManager;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productWebsiteLink = $productWebsiteLink;
        $this->loggerHelper = $loggerHelper;
        $this->trackingConfigFactory = $trackingConfigFactory;
        $this->synchronizationConfig = $synchronizationConfig;
        $this->eventPublisher = $eventPublisher;
        $this->batchPublisher = $batchPublisher;
        $this->productCRUD = $productCRUD;
        $this->productSender = $productSender;
    }

    /**
     * Process attribute update
     *
     * @param Action $subject
     * @param $result
     * @param $productIds
     * @param $attrData
     * @param $storeId
     * @return Action
     */
    public function afterUpdateAttributes(Action $subject, $result, $productIds, $attrData, $storeId): Action
    {
        if (!$this->synchronizationConfig->isModelEnabled(ProductSender::MODEL)) {
            return $subject;
        }

        $attributes = $this->productSender->getAttributesToSelect($storeId);
        if (empty(array_intersect($attributes, array_keys($attrData)))) {
            return $subject;
        }

        if ($storeId == 0) {
            $products = [];
            foreach ($productIds as $productId) {
                foreach ($this->productWebsiteLink->getWebsiteIdsByProductId($productId) as $websiteId) {
                    if (!isset($products[$websiteId])) {
                        $products[$websiteId] = [];
                    }
                    $products[$websiteId][] = $productId;
                }
            }

            foreach ($products as $websiteId => $websiteProductIds) {
                foreach ($this->getEnabledStoreIds($websiteId) as $storeId) {
                    $this->handleUpdate($websiteProductIds, $storeId, $websiteId);
                }
            }
        } else {
            if ($this->synchronizationConfig->isModelEnabled(ProductSender::MODEL)) {
                $config = $this->trackingConfigFactory->get($storeId);
                $websiteId = $this->getWebsiteId($storeId);
                if ($websiteId && $config->isEventTrackingEnabled(self::EVENT)) {
                    $this->handleUpdate($productIds, $storeId, $websiteId);
                }
            }
        }

        return $subject;
    }

    /**
     * Process detachment of product websites
     *
     * @param Action $subject
     * @param $productIds
     * @param $websiteIds
     * @param $type
     * @return void
     */
    public function beforeUpdateWebsites(Action $subject, $productIds, $websiteIds, $type)
    {
        if ($this->synchronizationConfig->isModelEnabled(ProductSender::MODEL) && $type == 'remove') {
            foreach ($websiteIds as $websiteId) {
                $storeIds = $this->getEnabledStoreIds($websiteId);
                foreach ($storeIds as $storeId) {
                    $config = $this->trackingConfigFactory->get($storeId);
                    if ($config->isEventTrackingEnabled(self::EVENT)) {
                        $collection = $this->productCollectionFactory->create();
                        $collection
                            ->addStoreFilter($storeId)
                            ->addIdFilter($productIds);

                        $attributes = $this->productSender->getAttributesToSelect($storeId);
                        if (!empty($attributes)) {
                            $collection->addAttributeToSelect($attributes);
                        }

                        foreach($collection as $product) {
                            $this->handleDelete(
                                $product,
                                $storeId,
                                $websiteId,
                                $config->isEventMessageQueueEnabled(ProductDelete::EVENT_FOR_CONFIG)
                            );
                        }
                    }
                }
            }
        }
    }

    /**
     * Process attachment of product websites
     *
     * @param Action $subject
     * @param $result
     * @param $productIds
     * @param $websiteIds
     * @param $type
     * @return void
     */
    public function afterUpdateWebsites(Action $subject, $result, $productIds, $websiteIds, $type)
    {
        if ($this->synchronizationConfig->isModelEnabled(ProductSender::MODEL) && $type == 'add') {
            foreach ($websiteIds as $websiteId) {
                $storeIds = $this->getEnabledStoreIds($websiteId);
                foreach ($storeIds as $storeId) {
                    $config = $this->trackingConfigFactory->get($storeId);
                    if ($config->isEventTrackingEnabled(self::EVENT)) {
                        $this->batchPublisher->publish(
                            ProductSender::MODEL,
                            array_unique($productIds),
                            $storeId,
                            $websiteId
                        );
                    }
                }
            }
        }
    }

    /**
     * Handle item deletion from store
     *
     * @param $product
     * @param int $storeId
     * @param int $websiteId
     * @param bool $queueEnabled
     * @return void
     */
    protected function handleDelete($product, int $storeId, int $websiteId, bool $queueEnabled)
    {
        try {
            $addItemRequest = $this->productCRUD->prepareRequest(
                $product,
                $websiteId,
                1
            );

            if ($queueEnabled) {
                $this->eventPublisher->publish(
                    ProductDelete::EVENT,
                    $addItemRequest,
                    $storeId,
                    $product->getEntityId());
            } else {
                $this->productSender->deleteItem($addItemRequest, $storeId, $product->getEntityId());
            }
        } catch (\Exception $e) {
            if (!$e instanceof ApiException) {
                $this->loggerHelper->error($e);
            }
        }
    }

    protected function handleUpdate(array $productIds, int $storeId, int $websiteId)
    {
        $this->batchPublisher->publish(
            ProductSender::MODEL,
            array_unique($productIds),
            $storeId,
            $websiteId
        );
    }

    /**
     * Get all store ids for website id and event
     *
     * @param int $websiteId
     * @return array
     */
    protected function getEnabledStoreIds(int $websiteId): array
    {
        if (!isset($this->storeIds[$websiteId])) {
            $this->storeIds[$websiteId] = [];

            try {
                foreach ($this->storeManager->getWebsite($websiteId)->getStoreIds() as $storeId) {
                    $this->storeIds[$websiteId][] = $storeId;
                }
            } catch (LocalizedException $e) {
                $this->loggerHelper->debug($e);
            }
        }
        return $this->storeIds[$websiteId];
    }

    /**
     * Get website id by store id
     *
     * @param int $storeId
     * @return int|null
     */
    protected function getWebsiteId(int $storeId): ?int
    {
        try {
            return $this->storeManager->getStore($storeId)->getWebsiteId();
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }
}
