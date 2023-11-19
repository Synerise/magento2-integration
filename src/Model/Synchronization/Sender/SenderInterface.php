<?php

namespace Synerise\Integration\Model\Synchronization\Sender;

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
     * @param int|null $storeId
     * @return int
     */
    public function getPageSize(?int $storeId = null): int;
}