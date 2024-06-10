<?php

namespace Synerise\Integration\Model\Tracking;

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
     * @var Config[]
     */
    protected $config;

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
     * Create tracking config for given store ID
     *
     * @param int $storeId
     * @return Config
     */
    public function create(int $storeId): Config
    {
        return $this->objectManager->create(Config::class, ['storeId' => $storeId]);
    }

    /**
     * Get tracking config for given store ID
     *
     * @param int $storeId
     * @return Config
     */
    public function get(int $storeId): Config
    {
        if (!isset($this->config[$storeId])) {
            $this->config[$storeId] = $this->create($storeId);
        }

        return $this->config[$storeId];
    }
}
