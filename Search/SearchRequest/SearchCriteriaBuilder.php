<?php

namespace Synerise\Integration\Search\SearchRequest;

use Magento\Catalog\Model\Config\LayerCategoryConfig;
use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Magento\Framework\ObjectManagerInterface;
use Synerise\Integration\Search\Attributes\Config;

class SearchCriteriaBuilder
{
    public const TYPE_LISTING = 'listing';

    public const TYPE_SEARCH = 'search';

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var LayerCategoryConfig
     */
    private $layerCategoryConfig;

    /**
     * @var DefaultFiltersBuilder
     */
    private $defaultFiltersBuilder;

    /**
     * @var Config
     */
    private $attributesConfig;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param LayerCategoryConfig $layerCategoryConfig
     * @param DefaultFiltersBuilder $defaultFiltersBuilder
     * @param Config $attributesConfig
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        LayerCategoryConfig $layerCategoryConfig,
        DefaultFiltersBuilder $defaultFiltersBuilder,
        Config $attributesConfig
    ) {
        $this->objectManager = $objectManager;
        $this->layerCategoryConfig = $layerCategoryConfig;
        $this->defaultFiltersBuilder = $defaultFiltersBuilder;
        $this->attributesConfig = $attributesConfig;
    }

    /**
     * Build set of search criteria
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @param string $type
     * @return Criteria
     */
    public function build(SearchCriteriaInterface $searchCriteria, string $type): Criteria
    {
        $filters = $this->defaultFiltersBuilder->build();
        $data = [
            'facets' => $this->prepareFacets($this->getFilterable($type)),
            'limit' => $searchCriteria->getPageSize() ?: 12,
            'page' => $searchCriteria->getCurrentPage() + 1,
        ];

        $subfilters = [];
        foreach($searchCriteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                $field = $filter->getField();

                if ($field == 'search_term') {
                    $data['query'] = $filter->getValue();
                } elseif ($fieldName = $this->attributesConfig->getMappedFieldName($field)) {
                    if (is_array($value = $filter->getValue())) {
                        $filters->setData($fieldName, ['in' => $value]);
                    } else {
                        $filters->setData($fieldName, ['eq' => $value]);
                    }
                } else {
                    list ($fieldName, $condition) = array_pad(explode(".", $field), 2, null);
                    if (in_array($condition, ['from', 'to'])) {
                        $subfilters[$fieldName] ??= [];
                        $subfilters[$fieldName][$condition] = $filter->getValue();
                    }
                }
            }
        }
        foreach ($subfilters as $fieldName => $subfilter) {
            $filters->setData($fieldName, $subfilter);
        }

        $data['filters'] = $filters;

        foreach ($searchCriteria->getSortOrders() as $key => $value) {
            if ($key != 'relevance') {
                $data['sort_by'] = $key;
            }
            $data['ordering'] = strtolower($value);
            break;
        }

        return $this->objectManager->create(Criteria::class, ['data' => $data]);
    }

    /**
     * Prepare facets for request
     *
     * @param array $facets
     * @return string[]
     */
    protected function prepareFacets(array $facets): array
    {
        if (!empty($facets)) {
            if ($this->layerCategoryConfig->isCategoryFilterVisibleInLayerNavigation()) {
                $facets[] = 'category_ids';
            }
        } else {
            $facets = ['*'];
        }

        return $facets;
    }

    /**
     * Get filterable attributes by request name
     *
     * @param string $type
     * @return array
     */
    protected function getFilterable(string $type): array
    {
        switch ($type) {
            case self::TYPE_LISTING:
                return array_values($this->attributesConfig->getFilterableInListing());
            case self::TYPE_SEARCH:
                return array_values($this->attributesConfig->getFilterableInSearch());
            default:
                return [];
        }
    }
}