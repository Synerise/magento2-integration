<?php

namespace Synerise\Integration\SyneriseApi\Config;

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
     * Create api config for given store ID
     *
     * @param int $storeId
     * @return Data
     */
    public function create(int $storeId): Data
    {
        return $this->objectManager->create(Data::class, ['storeId' => $storeId]);
    }
}
