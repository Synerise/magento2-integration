<?php

namespace Synerise\Integration\Search\Autocomplete;

use Magento\Framework\ObjectManagerInterface;
use Synerise\Integration\Search\Autocomplete\DataSource\DataSourceInterface;

class DataSourceFactory
{
    /**
     * Object Manager instance
     *
     * @var ObjectManagerInterface
     */
    protected $objectManager = null;

    /**
     * Factory constructor
     *
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Create DataSource
     *
     * @param string $type
     * @param array $sourceConfig
     * @return DataSourceInterface
     */
    public function create(string $type, array $sourceConfig): DataSourceInterface
    {
        return $this->objectManager->create($type, ['sourceConfig' => $sourceConfig]);
    }

}