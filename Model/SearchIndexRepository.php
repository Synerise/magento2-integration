<?php

namespace Synerise\Integration\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Synerise\Integration\Api\SearchIndexRepositoryInterface;
use Synerise\Integration\Model\SearchIndexFactory;
use Synerise\Integration\Model\SearchIndexInterface;

use Synerise\Integration\Model\ResourceModel\SearchIndex as SearchIndexResource;

class SearchIndexRepository implements SearchIndexRepositoryInterface
{
    /**
     * @var SearchIndexResource
     */
    private $resource;

    /**
     * @var SearchIndexFactory
     */
    private $factory;

    public function __construct(
        SearchIndexResource $resource,
        SearchIndexFactory $factory
    ) {
        $this->resource = $resource;
        $this->factory = $factory;
    }

    public function getById(int $id): SearchIndexInterface
    {
        $searchIndex = $this->factory->create();
        $this->resource->load($searchIndex, $id);

        if (!$searchIndex->getId()) {
            throw new NoSuchEntityException(__('SearchIndex with ID %1 does not exist', $id));
        }

        return $searchIndex;
    }

    public function getByStoreId(int $storeId): SearchIndexInterface
    {
        $searchIndex = $this->factory->create();
        $this->resource->load($searchIndex, $storeId, 'store_id');

        if (!$searchIndex->getId()) {
            throw new NoSuchEntityException(__('SearchIndex with ID %1 does not exist', $storeId));
        }

        return $searchIndex;
    }

    public function save(SearchIndexInterface $searchIndex): SearchIndexInterface
    {
        $this->resource->save($searchIndex);
        return $searchIndex;
    }

    public function delete(SearchIndexInterface $searchIndex): bool
    {
        $this->resource->delete($searchIndex);
        return true;
    }
}