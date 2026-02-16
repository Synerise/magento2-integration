<?php

namespace Synerise\Integration\ViewModel\Recommendations;

use Magento\Framework\ObjectManagerInterface;

class DataFactory
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
     * Create Recommendations data view model
     *
     * @param array $data
     * @return Data
     */
    public function create(array $data): Data
    {
        return $this->objectManager->create(Data::class, ['data' => $data]);
    }
}