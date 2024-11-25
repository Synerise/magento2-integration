<?php

namespace Synerise\Integration\Search\SearchResponse\Aggregation;

use Synerise\Integration\Search\SearchResponse\Aggregation\Bucket\BucketBuilderInterface;

interface BucketBuilderFactoryInterface
{
    public function get(string $type): BucketBuilderInterface;
}