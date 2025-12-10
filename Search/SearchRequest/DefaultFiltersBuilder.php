<?php

namespace Synerise\Integration\Search\SearchRequest;

use Magento\CatalogInventory\Model\Configuration;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class DefaultFiltersBuilder
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Build default filters
     *
     * @return Filters
     */
    public function build(): Filters
    {
        $defaults = [
            'deleted' => ['neq' => 1],
            'entity_id' => ['is' => 'DEFINED']
        ];

        if (!$this->showOutOfStock()) {
            $defaults['is_salable'] = ['eq' => 'true'];
        }

        return new Filters($defaults);
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