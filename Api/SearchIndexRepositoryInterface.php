<?php

namespace Synerise\Integration\Api;

use Synerise\Integration\Model\SearchIndexInterface;

interface SearchIndexRepositoryInterface
{
    public function getById(int $id): SearchIndexInterface;

    public function getByStoreId(int $storeId): SearchIndexInterface;

    public function save(SearchIndexInterface $searchIndex): SearchIndexInterface;

    public function delete(SearchIndexInterface $searchIndex): bool;
}