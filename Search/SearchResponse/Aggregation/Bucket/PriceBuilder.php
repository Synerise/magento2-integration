<?php

namespace Synerise\Integration\Search\SearchResponse\Aggregation\Bucket;

use Magento\Framework\Search\Adapter\OptionsInterface;

class PriceBuilder implements BucketBuilderInterface
{
    /**
     * @var OptionsInterface
     */
    protected $options;

    public function __construct(
        OptionsInterface $options
    ){
        $this->options = $options;
    }

    public function build(array $facet): array
    {
        if (!empty($facet)) {
            $maxPrice = max(array_keys($facet));
            $index = 1;

            do {
                $range = pow(10, strlen(floor($maxPrice)) - $index);
                $grouped = $this->groupByRange($facet, $range);
                $count = count($grouped);
                $index++;
            } while ($range > $this->getMinRangePower() && $count < 2);

            return $this->formatGroup($grouped, $range);
        }

        return [];
    }

    protected function groupByRange($facet, $range)
    {
        $grouped = [];
        foreach ($facet as $value => $count) {
            $key = floor($value / $range);
            $grouped[$key] = isset($grouped[$key]) ? $grouped[$key] + $count : $count;
        }

        return $grouped;
    }

    protected function formatGroup($grouped, $range)
    {
        $formatted = [];
        foreach($grouped as $min => $count) {
            $from = $min * $range;
            $to = ($min + 1) * $range;
            $value = $from . '_' . $to;

            $formatted[$value] = [
                'from' => $from,
                'to' => $to,
                'value' => $value,
                'count' => $count
            ];
        }

        return $formatted;
    }

    /**
     * Return Minimal range power.
     *
     * @return int
     */
    protected function getMinRangePower()
    {
        $options = $this->options->get();

        return $options['min_range_power'];
    }
}