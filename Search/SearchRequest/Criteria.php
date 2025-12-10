<?php

namespace Synerise\Integration\Search\SearchRequest;

use Magento\Framework\Api\AbstractSimpleObject;

class Criteria extends AbstractSimpleObject
{
    /**
     * Get facets
     *
     * @return array|null
     */
    public function getFacets(): ?array
    {
        return $this->_get('facets');
    }

    /**
     * Get filters
     *
     * @return Filters|null
     */
    public function getFilters(): ?Filters
    {
        return $this->_get('filters');
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
     * @return int|null
     */
    public function getLimit(): ?int
    {
        return $this->_get('limit');
    }

    /**
     * Get page
     *
     * @return int|null
     */
    public function getPage(): ?int
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