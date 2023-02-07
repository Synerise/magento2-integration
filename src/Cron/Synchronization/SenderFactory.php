<?php

namespace Synerise\Integration\Cron\Synchronization;

use Magento\Framework\ObjectManagerInterface;
use Synerise\Integration\Cron\Synchronization\Sender\AbstractSender;
use Synerise\Integration\Cron\Synchronization\Sender\Customer;
use Synerise\Integration\Cron\Synchronization\Sender\Order;
use Synerise\Integration\Cron\Synchronization\Sender\Product;
use Synerise\Integration\Cron\Synchronization\Sender\Subscriber;

class SenderFactory
{
    /**
     * string[]
     */
    protected $senders = [];
    
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

        $this->senders = [
            'customer' => Customer::class,
            'order' => Order::class,
            'product' => Product::class,
            'subscriber' => Subscriber::class
        ];
    }

    /**
     * @param string $name
     * @return AbstractSender
     */
    public function create(string $name): AbstractSender
    {
        if (!isset($this->senders[$name])) {
            throw new \InvalidArgumentException('Invalid sender name');
        }

        return $this->objectManager->get($this->senders[$name]);
    }
}