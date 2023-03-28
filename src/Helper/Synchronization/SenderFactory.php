<?php

namespace Synerise\Integration\Helper\Synchronization;

use Magento\Framework\ObjectManagerInterface;
use Synerise\Integration\Helper\Synchronization\Sender\AbstractSender;
use Synerise\Integration\Helper\Synchronization\Sender\Customer;
use Synerise\Integration\Helper\Synchronization\Sender\Order;
use Synerise\Integration\Helper\Synchronization\Sender\Product;
use Synerise\Integration\Helper\Synchronization\Sender\Subscriber;
use Synerise\Integration\Model\ApiConfig;

class SenderFactory
{
    const SENDER_CLASSES = [
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
     * @param int $storeId
     * @param ApiConfig $apiConfig
     * @param int|null $websiteId
     * @return AbstractSender
     */
    public function create(
        string $name,
        int $storeId,
        ApiConfig $apiConfig,
        ?int $websiteId = null
    ): AbstractSender
    {
        if (!isset(self::SENDER_CLASSES[$name])) {
            throw new \InvalidArgumentException('Invalid sender name');
        }

        return $this->objectManager->create(
            self::SENDER_CLASSES[$name],
            ['storeId' => $storeId, 'websiteId' => $websiteId, 'apiConfig' => $apiConfig]);
    }
}