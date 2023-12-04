<?php

namespace Synerise\Integration\MessageQueue\Consumer\Data\Item;

use Magento\Framework\ObjectManagerInterface;

class ConsumerFactory
{
    const CONSUMER_CLASSES = [
        'customer' => Customer::class,
        'order' => Order::class,
        'product' => Product::class,
        'subscriber' => Subscriber::class
    ];

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param string $name
     * @return ConsumerInterface
     */
    public function get(string $name): ConsumerInterface
    {
        if (!isset(self::CONSUMER_CLASSES[$name])) {
            throw new \InvalidArgumentException('Invalid sender name');
        }

        return $this->objectManager->get(self::CONSUMER_CLASSES[$name]);
    }
}