<?php

namespace Synerise\Integration\Search\Recommendation;

interface ConfigFactoryInterface
{
    /**
     * Create recommendation config for given store ID
     *
     * @param int $storeId
     * @return ConfigInterface
     */
    public function create(int $storeId = 0): ConfigInterface;
}