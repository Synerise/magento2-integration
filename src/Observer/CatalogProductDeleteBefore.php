<?php

namespace Synerise\Integration\Observer;

use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Synerise\ApiClient\ApiException;
use Synerise\CatalogsApiClient\ApiException as CatalogsApiException;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\MessageQueue\Publisher\Event as Publisher;
use Synerise\Integration\Model\Synchronization\Config;
use Synerise\Integration\SyneriseApi\Sender\Data\Product as Sender;

class CatalogProductDeleteBefore implements ObserverInterface
{
    public const EVENT = 'catalog_product_delete_before';

    public const EVENT_FOR_CONFIG = 'catalog_product_delete_after';

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

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
     * @var Sender
     */
    protected $sender;

    /**
     * @param StoreManagerInterface $storeManager
     * @param Logger $loggerHelper
     * @param Config $synchronization
     * @param Tracking $trackingHelper
     * @param Publisher $publisher
     * @param Sender $sender
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Logger $loggerHelper,
        Config $synchronization,
        Tracking $trackingHelper,
        Publisher $publisher,
        Sender $sender
    ) {
        $this->storeManager = $storeManager;
        $this->loggerHelper = $loggerHelper;
        $this->synchronization = $synchronization;
        $this->trackingHelper = $trackingHelper;
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
                if (!$this->trackingHelper->isEventTrackingAvailable(self::EVENT_FOR_CONFIG, $storeId)) {
                    continue;
                }

                if (in_array($storeId, $enabledStores)) {

                    $addItemRequest = $this->sender->prepareItemRequest(
                        $product,
                        $this->getWebsiteIdByStoreId($storeId)
                    );

                    $value = $addItemRequest->getValue();
                    $value['deleted'] = 1;
                    $addItemRequest->setValue($value);

                    if ($this->trackingHelper->isEventMessageQueueAvailable(self::EVENT_FOR_CONFIG, $storeId)) {
                        $this->publisher->publish(self::EVENT, $addItemRequest, $storeId, $product->getEntityId());
                    } else {
                        $this->sender->deleteItem($addItemRequest, $storeId, $product->getEntityId());
                    }
                }
            }
        } catch (\Exception $e) {
            if (!$e instanceof CatalogsApiException && !$e instanceof ApiException) {
                $this->loggerHelper->getLogger()->error($e);
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
