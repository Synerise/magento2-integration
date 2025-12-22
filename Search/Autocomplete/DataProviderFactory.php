<?php

namespace Synerise\Integration\Search\Autocomplete;

use Magento\Framework\ObjectManagerInterface;
use Magento\Search\Model\Autocomplete\DataProviderInterface;
use Synerise\Integration\Search\Autocomplete\DataSource\DataSourceInterface;

class DataProviderFactory
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
     * Create DataProvider with given DataSource
     *
     * @param string $type
     * @param DataSourceInterface $dataSource
     * @return DataProviderInterface
     */
    public function create(string $type, DataSourceInterface $dataSource): DataProviderInterface
    {
        return $this->objectManager->create($type, ['dataSource' => $dataSource]);
    }

}