<?php

namespace Synerise\Integration\SyneriseApi;

use Magento\Framework\ObjectManagerInterface;
use Synerise\Integration\SyneriseApi\Sender\Data\Customer;
use Synerise\Integration\SyneriseApi\Sender\Data\Order;
use Synerise\Integration\SyneriseApi\Sender\Data\Product;
use Synerise\Integration\SyneriseApi\Sender\Data\SenderInterface;
use Synerise\Integration\SyneriseApi\Sender\Data\Subscriber;

class SenderFactory
{
    public const SENDER_CLASSES = [
        'customer' => Customer::class,
        'order' => Order::class,
        'product' => Product::class,
        'subscriber' => Subscriber::class
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
     * @return SenderInterface
     * @throws \InvalidArgumentException
     */
    public function get(string $name): SenderInterface
    {
        if (!isset(self::SENDER_CLASSES[$name])) {
            throw new \InvalidArgumentException('Invalid model');
        }

        return $this->objectManager->get(self::SENDER_CLASSES[$name]);
    }
}
