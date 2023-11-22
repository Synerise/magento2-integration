<?php

namespace Synerise\Integration\Model\Synchronization;

use Magento\Framework\ObjectManagerInterface;
use Synerise\Integration\Model\Synchronization\Provider\ProviderInterface;

/**
 * Factory class for @see ProviderInterface
 */
class ProviderFactory
{
    const PROVIDER_CLASSES = [
        'customer' => Provider\Customer::class,
        'order' => Provider\Order::class,
        'product' => Provider\Product::class,
        'subscriber' => Provider\Subscriber::class
    ];

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
     * Create class instance with specified parameters
     *
     * @param string $name
     * @return ProviderInterface
     * @throws \InvalidArgumentException
     */
    public function get(string $name)
    {
        if (!isset(self::PROVIDER_CLASSES[$name])) {
            throw new \InvalidArgumentException('Invalid model');
        }

        return $this->objectManager->get(self::PROVIDER_CLASSES[$name]);
    }
}