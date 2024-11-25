<?php

namespace Synerise\Integration\Search;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Search\EngineResolverInterface;

class QuickSearchEngineResolver implements EngineResolverInterface
{
    /**
     * @var EngineResolverInterface
     */
    private $defaultEngineResolver;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $scopeType;

    /**
     * @var string|null
     */
    private $scopeCode;

    /**
     * @var array
     */
    private $engines;

    /**
     * @param EngineResolverInterface $defaultEngineResolver
     * @param ScopeConfigInterface $scopeConfig
     * @param array $engines
     * @param $path
     * @param $scopeType
     * @param null $scopeCode
     */
    public function __construct(
        EngineResolverInterface $defaultEngineResolver,
        ScopeConfigInterface $scopeConfig,
        array $engines,
        $path,
        $scopeType,
        $scopeCode = null
    ) {
        $this->defaultEngineResolver = $defaultEngineResolver;
        $this->scopeConfig = $scopeConfig;
        $this->path = $path;
        $this->scopeType = $scopeType;
        $this->scopeCode = $scopeCode;
        $this->engines = $engines;
    }

    public function getCurrentSearchEngine()
    {
        $engine = $this->scopeConfig->getValue(
            $this->path,
            $this->scopeType,
            $this->scopeCode
        );

        if (in_array($engine, $this->engines)) {
            return $engine;
        } else {
            return $this->defaultEngineResolver->getCurrentSearchEngine();
        }
    }
}