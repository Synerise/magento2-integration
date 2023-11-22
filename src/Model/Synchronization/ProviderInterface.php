<?php

namespace Synerise\Integration\Model\Synchronization;

interface ProviderInterface
{

    public function getCollection();

    public function addStoreFilter($storeId);

    public function getCurrentLastId($storeId);

    public function createCollection();
}