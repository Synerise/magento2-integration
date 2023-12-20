<?php

namespace Synerise\Integration\SyneriseApi\Sender\Data;

use Synerise\ApiClient\ApiException;
use Synerise\CatalogsApiClient\ApiException as CatalogsApiException;

interface SenderInterface
{
    /**
     * @param $collection
     * @param int $storeId
     * @param int|null $websiteId
     * @return mixed
     * @throws ApiException | CatalogsApiException
     */
    public function sendItems($collection, int $storeId, ?int $websiteId = null);

    /**
     * @param int $storeId
     * @return string[]
     */
    public function getAttributesToSelect(int $storeId): array;
}