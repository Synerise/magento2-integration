<?php

namespace Synerise\Integration\Search\SearchRequest;

use Magento\Catalog\Model\Config\LayerCategoryConfig;
use Magento\CatalogInventory\Model\Configuration;
use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Synerise\Integration\Search\Attributes\Config;

class SearchCriteriaBuilder
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Config
     */
    private $attributesConfig;

    /**
     * @var LayerCategoryConfig
     */
    private $layerCategoryConfig;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $attributesConfig
     * @param LayerCategoryConfig $layerCategoryConfig
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        ScopeConfigInterface $scopeConfig,
        Config $attributesConfig,
        LayerCategoryConfig $layerCategoryConfig
    ) {
        $this->objectManager = $objectManager;
        $this->scopeConfig = $scopeConfig;
        $this->attributesConfig = $attributesConfig;
        $this->layerCategoryConfig = $layerCategoryConfig;
    }

    /**
     * Build set of search criteria
     *
     * @param SearchCriteriaInterface|null $searchCriteria
     * @return SearchCriteria
     */
    public function build(?SearchCriteriaInterface $searchCriteria = null): SearchCriteria
    {
        $data = [
            'filters' => $this->getDefaultFilters(),
        ];

        if (!empty($searchCriteria)) {
            $data['facets'] = $this->prepareFacets($this->getFilterable($searchCriteria));

            foreach($searchCriteria->getFilterGroups() as $filterGroup) {
                foreach ($filterGroup->getFilters() as $filter) {
                    $field = $filter->getField();

                    if ($field == 'search_term') {
                        $data['query'] = $filter->getValue();
                    } elseif ($fieldName = $this->attributesConfig->getMappedFieldName($field)) {
                        if (is_array($value = $filter->getValue())) {
                            $data['filters'][$fieldName]['in'] = $value;
                        } else {
                            $data['filters'][$fieldName]['eq'] = $value;
                        }
                    } else {
                        list ($fieldName, $condition) = array_pad(explode(".", $field), 2, null);
                        if (in_array($condition, ['from', 'to'])) {
                            $data['filters'][$fieldName][$condition] = $filter->getValue();
                        }
                    }
                }
            }

            $data['limit'] = $searchCriteria->getPageSize() ?: 12;
            $data['page'] = $searchCriteria->getCurrentPage() + 1;

            $data['sort_by'] = 'entity_id';
            $data['ordering'] = 'asc';
            foreach ($searchCriteria->getSortOrders() as $key => $value) {
                $data['sort_by'] = $key;
                $data['ordering'] = strtolower($value);
                break;
            }
        }

        return $this->objectManager->create(SearchCriteria::class, ['data' => $data]);
    }

    /**
     * Prepare facets for request
     *
     * @param array $facets
     * @return string[]
     */
    public function prepareFacets(array $facets): array
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
     * @param SearchCriteriaInterface $searchCriteria
     * @return array
     */
    protected function getFilterable(SearchCriteriaInterface $searchCriteria): array
    {
        switch ($searchCriteria->getRequestName()) {
            case 'catalog_view_container':
                return array_values($this->attributesConfig->getFilterableInListing());
            case 'quick_search_container':
                return array_values($this->attributesConfig->getFilterableInSearch());
            default:
                return [];
        }
    }

    /**
     * Get default filters
     *
     * @return array
     */
    protected function getDefaultFilters(): array
    {
        $defaults = [
            'deleted' => ['neq' => 1],
            'entity_id' => ['is' => 'DEFINED'],
            $this->attributesConfig->getMappedFieldName('visibility') => ['in' => [3, 4]]
        ];

        if ($this->showOutOfStock()) {
            $defaults['is_salable'] = ['eq' => 'true'];
        }

        return $defaults;
    }

    /**
     * @return boolean
     */
    protected function showOutOfStock(): bool
    {
        return $this->scopeConfig->getValue(
            Configuration::XML_PATH_SHOW_OUT_OF_STOCK,
            ScopeInterface::SCOPE_STORE
        );
    }
}