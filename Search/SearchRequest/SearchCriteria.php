<?php

namespace Synerise\Integration\Search\SearchRequest;

use Magento\Framework\Api\AbstractSimpleObject;

class SearchCriteria extends AbstractSimpleObject
{
    public $expression = [
        'eq' => '%s == "%s"',
        'in' => '%s IN [%s]',
        'is' => '%s IS %s',
        'from' => '%s >= %s',
        'to' => '%s <= %s'
    ];

    /**
     * Get facets
     *
     * @return array
     */
    public function getFacets(): array
    {
        return $this->_get('facets');
    }

    /**
     * Get filters
     *
     * @return string|null
     */
    public function getFilters(): ?string
    {
        $formatted = [];
        foreach($this->_get('filters') as $field => $filter) {
            foreach($filter as $condition => $value) {
                if (isset($this->expression[$condition])) {
                    if (is_array($value)) {
                        $value = sprintf('"%s"', implode('", "', $value));
                    }
                    $formatted[] = sprintf($this->expression[$condition], $field, $value);
                }
            }
        }
        return implode(' AND ', $formatted);
    }

    /**
     * Get search term
     *
     * @return string|null
     */
    public function getQuery(): ?string
    {
        return $this->_get('query');
    }

    /**
     * Get limit
     *
     * @return int
     */
    public function getLimit(): int
    {
        return $this->_get('limit');
    }

    /**
     * Get page
     *
     * @return int
     */
    public function getPage(): int
    {
        return $this->_get('page');
    }

    /**
     * Get ordering
     *
     * @return string|null
     */
    public function getOrdering(): ?string
    {
        return $this->_get('ordering');
    }

    /**
     * Get sort by
     *
     * @return string|null
     */
    public function getSortBy(): ?string
    {
        return $this->_get('sort_by');
    }
}