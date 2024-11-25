<?php

namespace Synerise\Integration\Search\SearchResponse\Aggregation;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Search\EngineResolverInterface;

class BucketBuilderFactoryProvider implements BucketBuilderFactoryProviderInterface
{
    /**
     * @var BucketBuilderFactoryInterface
     */
    protected $factory;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * Pool of existing engines
     *
     * @var array
     */
    private $enginePool;

    /**
     * @var EngineResolverInterface
     */
    private $engineResolver;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param array $engines
     * @param EngineResolverInterface $engineResolver
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        array $engines,
        EngineResolverInterface $engineResolver
    ) {
        $this->objectManager = $objectManager;
        $this->enginePool = $engines;
        $this->engineResolver = $engineResolver;
    }

    /**
     * Get bucket builder factory singleton
     *
     * @return BucketBuilderFactoryInterface
     */
    public function get(): BucketBuilderFactoryInterface
    {
        if (!$this->factory) {
            $currentEngine = $this->engineResolver->getCurrentSearchEngine();
            if (!isset($this->enginePool[$currentEngine]) && !isset($this->enginePool['default'])) {
                throw new \LogicException(
                    'There is no such engine: ' . $currentEngine
                );
            }
            $factoryClassName = $this->enginePool[$currentEngine] ?? $this->enginePool['default'];

            $factory = $this->objectManager->create($factoryClassName);
            if (false === $factory instanceof BucketBuilderFactoryInterface) {
                throw new \LogicException(
                    $factory . ' doesn\'t implement ' . BucketBuilderFactoryInterface::class
                );
            }

            $this->factory = $factory;
        }

        return $this->factory;
    }
}