<?php

namespace Synerise\Integration\Search\Recommendation\ZeroStateRecent;

use Magento\Framework\ObjectManagerInterface;
use Synerise\Integration\Search\Recommendation\ConfigFactoryInterface;
use Synerise\Integration\Search\Recommendation\ConfigInterface;

class ConfigFactory implements ConfigFactoryInterface
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
     * @return ConfigInterface
     */
    public function create(int $storeId = 0): ConfigInterface
    {
        return $this->objectManager->create(Config::class, ['storeId' => $storeId]);
    }
}
