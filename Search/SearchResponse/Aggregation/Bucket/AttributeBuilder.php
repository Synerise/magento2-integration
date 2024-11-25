<?php

namespace Synerise\Integration\Search\SearchResponse\Aggregation\Bucket;

class AttributeBuilder implements BucketBuilderInterface
{
    public function build(array $facet): array
    {
        $rawAggregation = [];
        foreach ($facet as $value => $count) {
            $rawAggregation[] = [
                'value' => $value,
                'count' => $count
            ];
        }
        return $rawAggregation;
    }
}