<?php

namespace Synerise\Integration\Model\Synchronization\MessageQueue\Data\Single;

use Magento\Framework\ObjectManagerInterface;
use Synerise\Integration\Model\Synchronization\MessageQueue\Data\Single\Consumer\Customer;
use Synerise\Integration\Model\Synchronization\MessageQueue\Data\Single\Consumer\Order;
use Synerise\Integration\Model\Synchronization\MessageQueue\Data\Single\Consumer\Product;
use Synerise\Integration\Model\Synchronization\MessageQueue\Data\Single\Consumer\Subscriber;

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