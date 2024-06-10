<?php

namespace Synerise\Integration\Observer\Data;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\ImportExport\Model\Import;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\MessageQueue\Publisher\Event as Publisher;
use Synerise\Integration\Model\Synchronization\Config;
use Synerise\Integration\Model\Tracking\ConfigFactory;
use Synerise\Integration\Plugin\ProductImport;
use Synerise\Integration\SyneriseApi\Mapper\Data\ProductCRUD;
use Synerise\Integration\SyneriseApi\Sender\Data\Product as Sender;

class ProductImportBunchDelete implements ObserverInterface
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
     * @var ProductImport
     */
    protected $productImport;

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
     * @param Logger $loggerHelper
     * @param ConfigFactory $configFactory
     * @param Config $synchronization
     * @param ProductImport $productImport
     * @param ProductCRUD $productCRUD
     * @param Publisher $publisher
     * @param Sender $sender
     */
    public function __construct(
        Logger $loggerHelper,
        ConfigFactory $configFactory,
        Config $synchronization,
        ProductImport $productImport,
        ProductCRUD $productCRUD,
        Publisher $publisher,
        Sender $sender
    ) {
        $this->loggerHelper = $loggerHelper;
        $this->configFactory = $configFactory;
        $this->synchronization = $synchronization;
        $this->productImport = $productImport;
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
        try {
            if (Import::BEHAVIOR_DELETE != $this->productImport->getOriginalBehavior()) {
                return;
            }

            if (!$this->synchronization->isModelEnabled(Sender::MODEL)) {
                return;
            }

            foreach ($this->productImport->getProductsToDelete() as $storeId => $storeData) {
                $config = $this->configFactory->get($storeId);

                foreach($storeData['products'] as $product) {
                    $entityId = $product->getEntityId();
                    $addItemRequest = $this->productCRUD->prepareRequest(
                        $product,
                        $storeData['website_id'],
                        1
                    );

                    if ($config->isEventMessageQueueEnabled(self::EVENT)) {
                        $this->publisher->publish(self::EVENT, $addItemRequest, $storeId, $entityId);
                    } else {
                        $this->sender->deleteItem($addItemRequest, $storeId, $entityId);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->loggerHelper->error($e);
        }
    }
}
