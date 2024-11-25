<?php

namespace Synerise\Integration\Search\SearchResponse\Aggregation;

interface BucketBuilderFactoryProviderInterface
{
    public function get(): BucketBuilderFactoryInterface;
}