<?php

namespace Synerise\Integration\SyneriseApi\Sender\Data;

use Synerise\ApiClient\ApiException;
use Synerise\CatalogsApiClient\ApiException as CatalogsApiException;

interface SenderInterface
{
    /**
     * Send items
     *
     * @param mixed $collection
     * @param int $storeId
     * @param int|null $websiteId
     * @return mixed
     * @throws ApiException | CatalogsApiException
     */
    public function sendItems($collection, int $storeId, ?int $websiteId = null);

    /**
     * Get Attributes to collection
     *
     * @param int $storeId
     * @return string[]
     */
    public function getAttributesToSelect(int $storeId): array;
}
