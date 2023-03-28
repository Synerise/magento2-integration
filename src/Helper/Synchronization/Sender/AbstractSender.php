<?php

namespace Synerise\Integration\Helper\Synchronization\Sender;

use Magento\Framework\Data\Collection;
use Magento\Framework\Exception\LocalizedException;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\Helper\Synchronization\Results;
use Synerise\Integration\Model\ApiConfig;
use Synerise\Integration\Model\Cron\Status;

abstract class AbstractSender
{
    /**
     * @var mixed
     */
    protected $collectionFactory;

    /**
     * @var Results
     */
    protected $results;

    /**
     * @var Synchronization
     */
    protected $synchronization;

    /**
     * @var ApiConfig|null
     */
    protected $apiConfig;

    /**
     * @var int
     */
    protected $storeId;

    /**
     * @var int|null
     */
    protected $websiteId;

    public function __construct(
        Results $results,
        Synchronization $synchronization,
        $collectionFactory,
        int $storeId,
        ApiConfig $apiConfig,
        ?int $websiteId = null
    )
    {
        $this->results = $results;
        $this->synchronization = $synchronization;
        $this->collectionFactory = $collectionFactory;
        $this->storeId = $storeId;
        $this->apiConfig = $apiConfig;
        $this->websiteId = $websiteId;
    }

    /**
     * @param $collection
     * return array|null
     * @throws ApiException
     */
    abstract public function sendItems($collection): ?array;

    /**
     * @param array $entityIds
     * @throws LocalizedException
     */
    public function deleteItemsFromQueue(array $entityIds)
    {
        $this->synchronization->deleteItemsFromQueue(static::MODEL, $this->getStoreId(), $entityIds);
    }

    /**
     * @param Status $status
     * @return Collection
     * @throws LocalizedException
     */
    public function getCollectionFilteredByIdRange(Status $status): Collection
    {
        return $this->createCollectionWithScope()
            ->addFieldToFilter(
                static::ENTITY_ID,
                ['gt' => $status->getStartId()]
            )
            ->addFieldToFilter(
                static::ENTITY_ID,
                ['lteq' => $status->getStopId()]
            )
            ->setOrder(static::ENTITY_ID, 'ASC')
            ->setPageSize($this->getPageSize());
    }

    /**
     * @param string[] $entityIds
     * @return Collection
     * @throws LocalizedException
     */
    public function getCollectionFilteredByEntityIds(array $entityIds): Collection
    {
        return $this->createCollectionWithScope()
            ->addFieldToFilter(
                static::ENTITY_ID,
                ['in' => $entityIds]
            )
            ->setOrder(static::ENTITY_ID, 'ASC')
            ->setPageSize($this->getPageSize());
    }

    /**
     * @return int
     */
    public function getCurrentLastId(): int
    {
        $collection = $this->createCollectionWithScope()
            ->addFieldToSelect(static::ENTITY_ID)
            ->setOrder(static::ENTITY_ID, 'DESC')
            ->setPageSize(1);

        $item = $collection->getFirstItem();

        return $item ? $item->getData(static::ENTITY_ID) : 0;
    }

    /**
     * Create sender specific collection.
     *
     * @return Collection
     */
    abstract protected function createCollectionWithScope(): Collection;

    /**
     * @return int
     */
    public function getPageSize(): int
    {
        return $this->synchronization->getPageSize($this->getStoreId());
    }

    /**
     * Check if model is enabled for synchronization.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return in_array(static::MODEL, $this->synchronization->getEnabledModels());
    }

    /**
     * @return string
     */
    public function getEntityIdField(): string
    {
        return static::ENTITY_ID;
    }

    /**
     * @return array
     */
    public function getEnabledStores(): array
    {
        return $this->synchronization->getEnabledStores();
    }

    /**
     * @return ApiConfig
     */
    public function getApiConfig(): ApiConfig
    {
        return $this->apiConfig;
    }

    /**
     * @return int
     */
    public function getStoreId(): int
    {
        return $this->storeId;
    }

    /**
     * @return int|null
     */
    public function getWebsiteId(): ?int
    {
        return $this->websiteId;
    }
}
