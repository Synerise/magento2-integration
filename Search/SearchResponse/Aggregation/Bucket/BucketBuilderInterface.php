<?php

namespace Synerise\Integration\Search\SearchResponse\Aggregation\Bucket;

interface BucketBuilderInterface
{
    public function build(array $facet): array;
}