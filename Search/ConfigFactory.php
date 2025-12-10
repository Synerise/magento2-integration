<?php

namespace Synerise\Integration\Search;

use Magento\Framework\ObjectManagerInterface;

class ConfigFactory
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
     * Create search config for given store ID
     *
     * @param int $storeId
     * @return Config
     */
    public function create(int $storeId = 0): Config
    {
        return $this->objectManager->create(Config::class, ['storeId' => $storeId]);
    }
}
