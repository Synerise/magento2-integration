<?php

namespace Synerise\Integration\SyneriseApi\Sender\Data;

use InvalidArgumentException;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\ValidatorException;
use Synerise\ApiClient\ApiException;
use Synerise\CatalogsApiClient\Api\ItemsApi;
use Synerise\CatalogsApiClient\ApiException as CatalogsApiException;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Model\Workspace\ConfigFactory as WorkspaceConfigFactory;
use Synerise\Integration\SyneriseApi\Catalogs\Config;
use Synerise\Integration\SyneriseApi\Mapper\Data\ProductCRUD;
use Synerise\Integration\SyneriseApi\Sender\AbstractSender;
use Synerise\Integration\SyneriseApi\ConfigFactory;
use Synerise\Integration\SyneriseApi\InstanceFactory;

class Product extends AbstractSender implements SenderInterface
{
    public const MODEL = 'product';

    public const ENTITY_ID = 'entity_id';

    public const API_EXCEPTION = CatalogsApiException::class;

    /**
     * @var ProductCRUD
     */
    protected $productCRUD;

    /**
     * @var Config
     */
    protected $catalogsConfig;

    /**
     * @var AdapterInterface
     */
    protected $connection;

    /**
     * @param Logger $loggerHelper
     * @param ConfigFactory $configFactory
     * @param InstanceFactory $apiInstanceFactory
     * @param WorkspaceConfigFactory $workspaceConfigFactory
     * @param ProductCRUD $productCRUD
     * @param Config $catalogsConfig
     * @param ResourceConnection $resource
     */
    public function __construct(
        Logger $loggerHelper,
        ConfigFactory $configFactory,
        InstanceFactory $apiInstanceFactory,
        WorkspaceConfigFactory $workspaceConfigFactory,
        ProductCRUD $productCRUD,
        Config $catalogsConfig,
        ResourceConnection $resource
    ) {
        $this->productCRUD = $productCRUD;
        $this->catalogsConfig = $catalogsConfig;
        $this->connection = $resource->getConnection();

        parent::__construct($loggerHelper, $configFactory, $apiInstanceFactory, $workspaceConfigFactory);
    }

    /**
     * Send items
     *
     * @param Collection $collection
     * @param int $storeId
     * @param int|null $websiteId
     * @return void
     * @throws CatalogsApiException
     * @throws NoSuchEntityException|ValidatorException|ApiException
     */
    public function sendItems($collection, int $storeId, ?int $websiteId = null)
    {
        if (!$collection->getSize()) {
            return;
        }

        if (!$websiteId) {
            throw new InvalidArgumentException('Website id required for Product');
        }

        $addItemRequest = [];
        $ids = [];

        /** @var $product \Magento\Catalog\Model\Product */
        foreach ($collection as $product) {
            $ids[] = $product->getEntityId();
            $addItemRequest[] = $this->productCRUD->prepareRequest($product, $websiteId);
        }

        $this->addItemsBatchWithInvalidCatalogIdCatch($addItemRequest, $storeId);
        $this->markItemsAsSent($ids, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function getAttributesToSelect(int $storeId): array
    {
        return $this->productCRUD->getAttributesToSelect($storeId);
    }

    /**
     * Delete items
     *
     * @param mixed $payload
     * @param int $storeId
     * @param int|null $entityId
     * @return void
     * @throws ApiException
     * @throws CatalogsApiException
     * @throws ValidatorException
     */
    public function deleteItem($payload, int $storeId, ?int $entityId)
    {
        $this->addItemsBatchWithInvalidCatalogIdCatch([$payload], $storeId);
        if ($entityId) {
            $this->deleteStatus([$entityId], $storeId);
        }
    }

    /**
     * Add items batch with catalog ID catch
     *
     * @param mixed $addItemRequest
     * @param int $storeId
     * @return void
     * @throws CatalogsApiException
     * @throws ValidatorException
     * @throws ApiException
     */
    public function addItemsBatchWithInvalidCatalogIdCatch($addItemRequest, int $storeId)
    {
        try {
            $this->addItemsBatch(
                $storeId,
                $this->catalogsConfig->getCatalogId($storeId),
                $addItemRequest
            );
        } catch (CatalogsApiException $e) {
            if ($e->getCode() == 404 || ($e->getCode() == 403 && $this->isStoreForbiddenException($e))) {
                $this->catalogsConfig->resetByScopeId($storeId);
                $this->addItemsBatch(
                    $storeId,
                    $this->catalogsConfig->getCatalogId($storeId),
                    $addItemRequest
                );
            } else {
                throw $e;
            }
        }
    }

    /**
     * Add items batch
     *
     * @param int $storeId
     * @param int $catalogId
     * @param mixed $payload
     * @return void
     * @throws CatalogsApiException
     * @throws ValidatorException
     * @throws ApiException
     */
    public function addItemsBatch(int $storeId, int $catalogId, $payload)
    {
        try {
            list ($body, $statusCode, $headers) = $this->sendWithTokenExpiredCatch(
                function () use ($storeId, $catalogId, $payload) {
                    $this->getItemsApiInstance($storeId)
                        ->addItemsBatchWithHttpInfo($catalogId, $payload);
                },
                $storeId
            );

            if ($statusCode == 207) {
                $this->loggerHelper->warning('Request partially accepted', ['response' => $body]);
            }
        } catch (CatalogsApiException $e) {
            $this->logApiException($e);
            throw $e;
        }
    }

    /**
     * Get Items API instance
     *
     * @param int $storeId
     * @return ItemsApi
     * @throws ApiException
     * @throws ValidatorException
     */
    protected function getItemsApiInstance(int $storeId): ItemsApi
    {
        return $this->getApiInstance('items', $storeId);
    }

    /**
     * Mark products as sent
     *
     * @param int[] $ids
     * @param int $storeId
     * @return void
     */
    protected function markItemsAsSent(array $ids, int $storeId = 0)
    {
        $data = [];
        foreach ($ids as $id) {
            $data[] = [
                'product_id' => $id,
                'store_id' => $storeId
            ];
        }
        $this->connection->insertOnDuplicate(
            $this->connection->getTableName('synerise_sync_product'),
            $data
        );
    }

    /**
     * Delete status
     *
     * @param int[] $entityIds
     * @param int $storeId
     * @return void
     */
    protected function deleteStatus(array $entityIds, int $storeId)
    {
        $this->connection->delete(
            $this->connection->getTableName('synerise_sync_product'),
            [
                'store_id = ?' => $storeId,
                'product_id IN (?)' => $entityIds,
            ]
        );
    }

    /**
     * Check if exception indicates forbidden access to specified store ID
     *
     * @param CatalogsApiException $e
     * @return false|int
     */
    protected function isStoreForbiddenException(CatalogsApiException $e)
    {
        return strpos($e->getResponseBody(), 'Some(Id');
    }
}
