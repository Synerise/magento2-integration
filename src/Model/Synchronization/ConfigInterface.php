<?php

namespace Synerise\Integration\Model\Synchronization;

interface ConfigInterface
{
    public function getAttributesToSelect(int $storeId);

    public function getPageSize(int $storeId): int;
}