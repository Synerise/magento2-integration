<?php

namespace Synerise\Integration\SyneriseApi\Sender\Data;

interface SenderInterface
{
    /**
     * @param $collection
     * @param int $storeId
     * @param int|null $websiteId
     * @return mixed
     */
    public function sendItems($collection, int $storeId, ?int $websiteId = null);

    /**
     * @param int $storeId
     * @return string[]
     */
    public function getAttributesToSelect(int $storeId): array;
}