<?php

namespace Synerise\Integration\Model\Synchronization;

interface SenderInterface
{
    /**
     * @param $collection
     * @param int $storeId
     * @param int|null $websiteId
     * @return mixed
     */
    public function sendItems($collection, int $storeId, ?int $websiteId = null);
}