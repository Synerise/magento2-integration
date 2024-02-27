<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\MessageQueue\Publisher\Data\Batch as Publisher;
use Synerise\Integration\Model\Synchronization\Config;
use Synerise\Integration\Model\Tracking\ConfigFactory;
use Synerise\Integration\SyneriseApi\Sender\Data\Product as Sender;

class ProductImportBunchSaveAfter implements ObserverInterface
{
    public const EVENT = 'catalog_product_import_bunch_save_after';

    /**
     * @var Logger
     */
    protected $loggerHelper;

    /**
     * @var ConfigFactory
     */
    protected $configFactory;

    /**
     * @var Config
     */
    protected $synchronization;

    /**
     * @var Publisher
     */
    protected $publisher;

    /**
     * @param Logger $loggerHelper
     * @param ConfigFactory $configFactory
     * @param Config $synchronization
     * @param Publisher $publisher
     */
    public function __construct(
        Logger $loggerHelper,
        ConfigFactory $configFactory,
        Config $synchronization,
        Publisher $publisher
    ) {
        $this->loggerHelper = $loggerHelper;
        $this->configFactory = $configFactory;
        $this->synchronization = $synchronization;
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
        try {
            if (!$this->synchronization->isModelEnabled(Sender::MODEL)) {
                return;
            }

            $productsByStore = [];
            $bunch = $observer->getEvent()->getData('bunch');
            foreach ($bunch as $product) {
                if (isset($product['entity_id']) && isset($product['store_id'])) {
                    $config = $this->configFactory->create($product['store_id']);
                    if ($config->isEventTrackingEnabled(self::EVENT)) {
                        $productsByStore[$product['store_id']][] = $product['entity_id'];
                    }
                }
            }

            $enabledStoreIds = $this->synchronization->getConfiguredStores();
            foreach ($productsByStore as $storeId => $entityIds) {
                if (in_array($storeId, $enabledStoreIds)) {
                    $this->publisher->schedule(Sender::MODEL, $entityIds, $storeId);
                }
            }
        } catch (\Exception $e) {
            $this->loggerHelper->error($e);
        }
    }
}
