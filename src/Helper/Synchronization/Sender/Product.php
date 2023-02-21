<?php

namespace Synerise\Integration\Helper\Synchronization\Sender;

use Exception;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Psr\Log\LoggerInterface;
use Synerise\CatalogsApiClient\ApiException;
use Synerise\CatalogsApiClient\Model\AddItem;
use Synerise\Integration\Helper\Api\Factory\ItemsApiFactory;
use Synerise\Integration\Helper\Api\Bags;
use Synerise\Integration\Helper\Api\BagsFactory;
use Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\Helper\Api\Update\Item as ItemHelper;
use Synerise\Integration\Helper\Synchronization\Results;
use Synerise\Integration\Model\ApiConfig;

class Product extends AbstractSender
{
    const MODEL = 'product';

    const ENTITY_ID = 'entity_id';

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var Bags
     */
    protected $bagsHelper;

    /**
     * @var BagsFactory
     */
    protected $bagsHelperFactory;

    /**
     * @var ItemHelper
     */
    protected $itemHelper;

    /**
     * @var ItemsApiFactory
     */
    protected $itemsApiFactory;

    public function __construct(
        LoggerInterface   $logger,
        CollectionFactory $collectionFactory,
        BagsFactory       $bagsHelperFactory,
        ItemHelper        $itemHelper,
        ItemsApiFactory   $itemsApiFactory,
        Results           $results,
        Synchronization   $synchronization,
        int               $storeId,
        ApiConfig         $apiConfig = null,
        ?int              $websiteId = null
    ) {
        $this->logger  = $logger;
        $this->bagsHelperFactory = $bagsHelperFactory;
        $this->itemHelper = $itemHelper;
        $this->itemsApiFactory = $itemsApiFactory;

        parent::__construct($results, $synchronization, $collectionFactory, $storeId, $apiConfig, $websiteId);
    }

    /**
     * @return \Magento\Framework\Data\Collection
     */
    protected function createCollectionWithScope(): \Magento\Framework\Data\Collection
    {
        return $this->collectionFactory->create()->addStoreFilter($this->getStoreId());
    }

    /**
     * @param Collection $collection
     * @return array|null
     * @throws ApiException
     * @throws Exception
     */
    public function sendItems($collection): ?array
    {
        $attributes = $this->itemHelper->getProductAttributesToSelect($this->getStoreId());
        $collection->addAttributeToSelect($attributes);

        $addItemRequest = [];
        $ids = [];

        /** @var $product \Magento\Catalog\Model\Product */
        foreach ($collection as $product) {
            $ids[] = $product->getEntityId();
            $addItemRequest[] = $this->itemHelper->prepareItemRequest($product, $attributes, $this->getWebsiteId());
        }

        $response = $this->sendItemsWithCatalogCheck($addItemRequest);
        $this->results->markAsSent(self::MODEL, $ids, $this->getStoreId());

        return $response;
    }

    /**
     * @return Bags
     */
    protected function getBagsHelper(): Bags
    {
        if (!isset($this->bagsHelper)) {
            $this->bagsHelper = $this->bagsHelperFactory->create(
                $this->getApiConfig()
            );
        }

        return $this->bagsHelper;
    }

    /**
     * @param AddItem[] $addItemRequest
     * @return array
     * @throws ApiException
     */
    protected function sendItemsWithCatalogCheck(array $addItemRequest): array
    {
        $catalogId = $this->itemHelper->getCatalogIdFromConfig($this->getStoreId()) ?:
            $this->getBagsHelper()->getOrAddBagAndSaveConfig($this->getStoreId());

        try {
            $response = $this->sendAddItemsBatchRequest($catalogId, $addItemRequest);
        } catch (Exception $e) {
            if ($e->getCode() === 404) {
                $catalogId = $this->getBagsHelper()->addBagAndSaveConfig($this->getStoreId());
                $response = $this->sendAddItemsBatchRequest($catalogId, $addItemRequest);
            } else {
                throw $e;
            }
        }

        return $response;
    }

    /**
     * @param int $catalogId
     * @param AddItem[] $addItemRequest
     * @return array
     * @throws ApiException
     */
    protected function sendAddItemsBatchRequest(int $catalogId, array $addItemRequest): array
    {
        $itemsApi = $this->itemsApiFactory->get($this->getApiConfig());
        list ($body, $statusCode, $headers) = $itemsApi
            ->addItemsBatchWithHttpInfo($catalogId, $addItemRequest);

        if (substr($statusCode, 0, 1) != 2) {
            throw new ApiException(sprintf('Invalid Status [%d]', $statusCode));
        } elseif ($statusCode == 207) {
            $this->logger->debug('Request accepted with errors', ['response' => $body]);
        }

        return [$body, $statusCode, $headers];
    }
}
