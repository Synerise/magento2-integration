<?php

namespace Synerise\Integration\Search\SearchResponse\Aggregation;

use Magento\Framework\ObjectManagerInterface;
use Synerise\Integration\Search\SearchResponse\Aggregation\Bucket\BucketBuilderInterface;

class BucketBuilderFactory implements BucketBuilderFactoryInterface
{
    /**
     * @var BucketBuilderInterface
     */
    protected $bucketBuilder;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * Pool of existing types
     *
     * @var array
     */
    private $typePool;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param array $types
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        array $types
    ) {
        $this->objectManager = $objectManager;
        $this->typePool = $types;
    }

    /**
     * Get bucket builder singleton singleton
     *
     * @param string $type
     * @return BucketBuilderInterface
     */
    public function get(string $type): BucketBuilderInterface
    {
        if (!isset($this->bucketBuilder[$type])) {
            $bucketBuilderClassName = $this->typePool[$type] ?? $this->typePool['default'];
            $bucketBuilder = $this->objectManager->get($bucketBuilderClassName);
            if (false === $bucketBuilder instanceof BucketBuilderInterface) {
                throw new \LogicException(
                    $type . ' doesn\'t implement ' . BucketBuilderInterface::class
                );
            }
            $this->bucketBuilder[$type] = $bucketBuilder;
        }

        return $this->bucketBuilder[$type];
    }
}