<?php

namespace Synerise\Integration\Observer\Data;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\MessageQueue\Publisher\Data\Batch as Publisher;
use Synerise\Integration\Model\Synchronization\Config;
use Synerise\Integration\Model\Tracking\ConfigFactory;
use Synerise\Integration\SyneriseApi\Sender\Data\Product as Sender;

class ProductImportBunch implements ObserverInterface
{
    public const EVENT = 'catalog_product_import_bunch_save_after';

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

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
     * @var array
     */
    protected $storeIds;

    /**
     * @param StoreManagerInterface $storeManager
     * @param Logger $loggerHelper
     * @param ConfigFactory $configFactory
     * @param Config $synchronization
     * @param Publisher $publisher
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Logger $loggerHelper,
        ConfigFactory $configFactory,
        Config $synchronization,
        Publisher $publisher
    ) {
        $this->storeManager = $storeManager;
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
            /** @var \Magento\CatalogImportExport\Model\Import\Product $adapter */
            $adapter = $observer->getEvent()->getData('adapter');

            foreach ($bunch as $rowData) {
                $newSku = $adapter->getNewSku($rowData['sku']);
                $websites = $adapter->getProductWebsites($rowData['sku']);
                foreach ($websites as $websiteId) {
                    foreach ($this->getWebsiteEnabledStoreIds($websiteId) as $storeId) {
                        if(!isset($productsByStore[$storeId])) {
                            $productsByStore[$storeId] = [
                                'entity_ids' => [],
                                'website_id' => $websiteId
                            ];
                        }
                        $productsByStore[$storeId]['entity_ids'][] = $newSku['entity_id'];
                    }
                }
            }

            foreach ($productsByStore as $storeId => $storeData) {
                $config = $this->configFactory->create($storeId);
                if ($config->isEventTrackingEnabled(self::EVENT)) {
                    $this->publisher->schedule(
                        Sender::MODEL,
                        array_unique($storeData['entity_ids']),
                        $storeId,
                        $storeData['website_id']
                    );
                }
            }
        } catch (\Exception $e) {
            $this->loggerHelper->error($e);
        }
    }


    /**
     * Get all sore ids where product is presented
     *
     * @param int $websiteId
     * @return array
     * @throws LocalizedException|NoSuchEntityException
     */
    public function getWebsiteEnabledStoreIds(int $websiteId): array
    {
        if (!isset($this->storeIds[$websiteId])) {
            $this->storeIds[$websiteId] = [];

            $enabledStoreIds = $this->synchronization->getConfiguredStores();
            foreach($this->storeManager->getWebsite($websiteId)->getStoreIds() as $storeId) {
                $config = $this->configFactory->create($storeId);
                if (in_array($storeId, $enabledStoreIds) && $config->isEventTrackingEnabled(self::EVENT)) {
                    $this->storeIds[$websiteId][] = $storeId;
                }
            }
        }
        return $this->storeIds[$websiteId];
    }
}
