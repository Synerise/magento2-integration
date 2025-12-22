<?php

namespace Synerise\Integration\Search\Autocomplete\DataSource;

use Magento\Framework\ObjectManagerInterface;

class DataFactory
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    /**
     * @param array $data
     * @return Data
     */
    public function create(array $data): Data
    {
        return $this->objectManager->create(Data::class, ['data' => $data]);
    }
}