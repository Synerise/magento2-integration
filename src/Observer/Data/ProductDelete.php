<?php

namespace Synerise\Integration\Observer\Data;

use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Synerise\ApiClient\ApiException;
use Synerise\CatalogsApiClient\ApiException as CatalogsApiException;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\MessageQueue\Publisher\Event as Publisher;
use Synerise\Integration\Model\Synchronization\Config;
use Synerise\Integration\Model\Tracking\ConfigFactory;
use Synerise\Integration\SyneriseApi\Mapper\Data\ProductCRUD;
use Synerise\Integration\SyneriseApi\Sender\Data\Product as Sender;

class ProductDelete implements ObserverInterface
{
    public const EVENT = 'catalog_product_delete_before';

    public const EVENT_FOR_CONFIG = 'catalog_product_delete_after';

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ConfigFactory
     */
    protected $configFactory;

    /**
     * @var Logger
     */
    protected $loggerHelper;

    /**
     * @var Config
     */
    protected $synchronization;

    /**
     * @var ProductCRUD
     */
    protected $productCRUD;

    /**
     * @var Publisher
     */
    protected $publisher;

    /**
     * @var Sender
     */
    protected $sender;

    /**
     * @param StoreManagerInterface $storeManager
     * @param ConfigFactory $configFactory
     * @param Logger $loggerHelper
     * @param Config $synchronization
     * @param ProductCRUD $productCRUD
     * @param Publisher $publisher
     * @param Sender $sender
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ConfigFactory $configFactory,
        Logger $loggerHelper,
        Config $synchronization,
        ProductCRUD $productCRUD,
        Publisher $publisher,
        Sender $sender
    ) {
        $this->storeManager = $storeManager;
        $this->configFactory = $configFactory;
        $this->loggerHelper = $loggerHelper;
        $this->synchronization = $synchronization;
        $this->productCRUD = $productCRUD;
        $this->publisher = $publisher;
        $this->sender = $sender;
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

        /** @var Product $product */
        $product = $observer->getEvent()->getProduct();

        try {
            $enabledStores = $this->synchronization->getConfiguredStores();
            $productStores = $product->getStoreIds();
            foreach ($productStores as $storeId) {
                $config = $this->configFactory->create($storeId);
                if (!$config->isEventTrackingEnabled(self::EVENT_FOR_CONFIG)) {
                    return;
                }

                if (in_array($storeId, $enabledStores)) {
                    $addItemRequest = $this->productCRUD->prepareRequest(
                        $product,
                        $this->getWebsiteIdByStoreId($storeId),
                        1
                    );

                    if ($config->isEventMessageQueueEnabled(self::EVENT_FOR_CONFIG)) {
                        $this->publisher->publish(self::EVENT, $addItemRequest, $storeId, $product->getEntityId());
                    } else {
                        $this->sender->deleteItem($addItemRequest, $storeId, $product->getEntityId());
                    }
                }
            }
        } catch (\Exception $e) {
            if (!$e instanceof CatalogsApiException && !$e instanceof ApiException) {
                $this->loggerHelper->error($e);
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
