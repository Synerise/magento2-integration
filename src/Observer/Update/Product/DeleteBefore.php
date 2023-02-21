<?php

namespace Synerise\Integration\Observer\Update\Product;

use Exception;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Synerise\CatalogsApiClient\ApiException;
use Synerise\CatalogsApiClient\Model\AddItem;
use Synerise\Integration\Helper\Api;
use Synerise\Integration\Helper\Api\Factory\ItemsApiFactory;
use Synerise\Integration\Helper\Api\BagsFactory;
use Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\Helper\Synchronization\Results;
use Synerise\Integration\Helper\Synchronization\Sender\Product as ProductSender;
use Synerise\Integration\Helper\Api\Update\Item;
use Synerise\Integration\Model\ApiConfig;
use Synerise\Integration\Observer\AbstractObserver;

class DeleteBefore  extends AbstractObserver implements ObserverInterface
{
    const EVENT = 'catalog_product_delete_after';

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var Item
     */
    protected $item;

    /**
     * @var BagsFactory
     */
    protected $bagsFactory;

    /**
     * @var ItemsApiFactory
     */
    protected $itemsApiFactory;

    /**
     * @var Results
     */
    private $results;

    /**
     * @var Synchronization
     */
    protected $synchronization;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface      $logger,
        Api                  $api,
        BagsFactory          $bagsFactory,
        Item                 $item,
        ItemsApiFactory      $itemsApiFactory,
        Results              $results,
        Synchronization      $synchronization
    ) {
        $this->api = $api;
        $this->bagsFactory = $bagsFactory;
        $this->item = $item;
        $this->itemsApiFactory = $itemsApiFactory;
        $this->results = $results;
        $this->synchronization = $synchronization;

        parent::__construct($scopeConfig, $logger);
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->isEventTrackingEnabled(self::EVENT)) {
            return;
        }

        /** @var Product $product */
        $product = $observer->getEvent()->getProduct();

        try {
            $this->results->deleteItem(ProductSender::MODEL, $product->getId());
            
            $enabledStores = $this->synchronization->getEnabledStores();
            $productStores = $product->getStoreIds();
            foreach($productStores as $storeId) {
                if(in_array($storeId, $enabledStores)) {
                    $storeProduct = $this->item->getProductById($product->getId(), $storeId);
                    if($storeProduct) {
                        $this->deleteItemWithCatalogCheck(
                            $storeProduct,
                            $this->api->getApiConfigByScope($product->getStoreId())
                        );
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Synerise Api request failed', ['exception' => $e]);
        }
    }

    /**
     * @param Product $product
     * @param ApiConfig $apiConfig
     * @return void
     * @throws ApiException
     * @throws Exception
     */
    public function deleteItemWithCatalogCheck(ProductInterface $product, ApiConfig $apiConfig)
    {
        $addItemRequest = $this->item->prepareItemRequest(
            $product,
            $this->item->getProductAttributesToSelect($product->getStoreId())
        );
        $addItemRequest->setValue(array_merge($addItemRequest->getValue(), ['deleted' => 1]));
        $this->sendItemWithCatalogCheck(
            $addItemRequest,
            $product->getStoreId(),
            $apiConfig
        );
    }

    /**
     * @param AddItem $addItemRequest
     * @param int $storeId
     * @param ApiConfig $apiConfig
     * @return array
     * @throws ApiException
     */
    protected function sendItemWithCatalogCheck(AddItem $addItemRequest, int $storeId, ApiConfig $apiConfig): array
    {
        $bagsHelper = $this->bagsFactory->create($apiConfig);
        $itemId = $this->item->getCatalogIdFromConfig($storeId) ?:
            $bagsHelper->getOrAddBagAndSaveConfig($storeId);

        try {
            $response = $this->sendAddItemRequest($itemId, $addItemRequest, $apiConfig);
        } catch (Exception $e) {
            if ($e->getCode() === 404) {
                $itemId = $bagsHelper->addBagAndSaveConfig($storeId);
                $response = $this->sendAddItemRequest($itemId, $addItemRequest, $apiConfig);
            } else {
                throw $e;
            }
        }

        return $response;
    }

    /**
     * @param int $itemId
     * @param AddItem $addItemRequest
     * @param ApiConfig $apiConfig
     * @return array
     * @throws ApiException
     */
    public function sendAddItemRequest(int $itemId, AddItem $addItemRequest, ApiConfig $apiConfig): array
    {
        $itemsApi = $this->itemsApiFactory->get($apiConfig);
        list ($body, $statusCode, $headers) = $itemsApi
            ->addItemsWithHttpInfo($itemId, $addItemRequest);

        if (substr($statusCode, 0, 1) != 2) {
            throw new ApiException(sprintf('Invalid Status [%d]', $statusCode));
        } elseif ($statusCode == 207) {
            $this->logger->debug('Request accepted with errors', ['response' => $body]);
        }

        return [$body, $statusCode, $headers];
    }
}
