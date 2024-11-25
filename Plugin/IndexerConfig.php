<?php

namespace Synerise\Integration\Plugin;

use Magento\CatalogSearch\Model\Indexer\Fulltext;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Search\EngineResolverInterface;
use Magento\Indexer\Model\Config;

class IndexerConfig
{
    public const XML_PATH_CATALOG_SEARCH_DISABLE_FULLTEXT = 'catalog/search/disable_fulltext';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var EngineResolverInterface
     */
    protected $engineResolver;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param EngineResolverInterface $engineResolver
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        EngineResolverInterface $engineResolver
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->engineResolver = $engineResolver;
    }

    /**
     * Remove full text index from config
     *
     * @param Config $subject
     * @param array $result
     * @return array
     */
    public function afterGetIndexers(Config $subject, array $result)
    {
        if ($this->shouldDisableIndex()) {
            unset($result[Fulltext::INDEXER_ID]);
        }
        return $result;
    }

    /**
     * Check conditions for disabling  index
     *
     * @return bool
     */
    protected function shouldDisableIndex(): bool
    {
        if ($this->engineResolver->getCurrentSearchEngine() != 'synerise_ai') {
            return false;
        }

        return $this->isFullTextIndexDisabled();
    }

    /**
     * Check if Index disable flag is set
     *
     * @return bool
     */
    protected function isFullTextIndexDisabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_CATALOG_SEARCH_DISABLE_FULLTEXT);
    }
}