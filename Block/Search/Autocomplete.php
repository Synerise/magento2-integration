<?php

namespace Synerise\Integration\Block\Search;

use Magento\Customer\CustomerData\JsLayoutDataProviderPoolInterface;
use Magento\Framework\Search\EngineResolverInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Search\Model\Query as SearchQuery;
use Magento\Store\Model\ScopeInterface;
use Synerise\Integration\Search\Recommendation\Config\Reader;

class Autocomplete extends EngineResolver
{
    /**
     * @var EngineResolverInterface
     */
    protected $engineResolver;

    public function __construct(
        Context $context,
        JsLayoutDataProviderPoolInterface $jsLayoutDataProvider,
        EngineResolverInterface $engineResolver,
        array $data = []
    ) {
        $this->scopeConfig = $context->getScopeConfig();

        if (isset($data['jsLayout'])) {
            $this->jsLayout = array_merge_recursive($jsLayoutDataProvider->getData(), $data['jsLayout']);
            unset($data['jsLayout']);
        } else {
            $this->jsLayout = $jsLayoutDataProvider->getData();
        }

        if (isset($this->jsLayout['components']) && isset($this->jsLayout['components']['autocomplete_search_results'])) {
            $this->jsLayout['components']['autocomplete_search_results']['minSearchLength'] = $this->getMinQueryLength();
            $this->jsLayout['components']['autocomplete_search_results']['isZeroStateEnabled'] = $this->isZeroStateEnabled();
        }

        parent::__construct($context, $engineResolver, $data);
    }


    /**
     * Retrieve minimum query length
     *
     * @return int|string
     */
    public function getMinQueryLength()
    {
        return $this->scopeConfig->getValue(
            SearchQuery::XML_PATH_MIN_QUERY_LENGTH,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Checks if the zero-state feature is enabled in the configuration.
     *
     * @return bool Returns true if the zero-state feature is enabled, false otherwise.
     */
    public function isZeroStateEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            Reader::AUTOCOMPLETE_ZERO_STATE_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }
}