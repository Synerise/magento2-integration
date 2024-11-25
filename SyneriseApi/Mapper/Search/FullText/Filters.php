<?php

namespace Synerise\Integration\SyneriseApi\Mapper\Search\FullText;

use Magento\Framework\Api\Search\SearchCriteriaInterface;

class Filters
{
    public function prepareFilters(SearchCriteriaInterface $searchCriteria): array
    {
        $searchTerm = null;

        $filters = [
            'deleted != 1',
            'is_salable == "true"',
            'entity_id IS DEFINED'
        ];

        foreach($searchCriteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                $field = $filter->getField();
                $value = $filter->getValue();

                if (in_array($field, ['category_ids_to_aggregate','price_dynamic_algorithm'])) {
                    continue;
                } elseif ($field == 'search_term') {
                    $searchTerm = $value;
                    continue;
                }

                if ($field == 'price.from') {
                    $filters[] = 'price >= '. $value;
                    continue;
                } elseif ($field == 'price.to') {
                    $filters[] = 'price <= '. $value;
                    continue;
                }

                if (!in_array($field, ['category_ids'])) {
                    $field .= '.id';
                }

                if (is_array($value)) {
                    $filters[] = $field . ' IN ["' . implode('", "', $value) . '"]';
                } else {
                    $filters[] = sprintf('%s == "%s"', $field, $value);
                }
            }
        }

        return [
            'attributes' => $filters,
            'search_term' => $searchTerm
        ];
    }
}