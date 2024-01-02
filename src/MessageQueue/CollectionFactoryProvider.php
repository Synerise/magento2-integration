<?php

namespace Synerise\Integration\MessageQueue;

use Magento\Framework\ObjectManagerInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory as SubscriberCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;

class CollectionFactoryProvider
{
    protected const COLLECTION_FACTORY_CLASSES = [
        'customer' => CustomerCollectionFactory::class,
        'order' => OrderCollectionFactory::class,
        'product' => ProductCollectionFactory::class,
        'subscriber' => SubscriberCollectionFactory::class
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
     * @return ProductCollectionFactory|CustomerCollectionFactory|OrderCollectionFactory|SubscriberCollectionFactory
     * @throws \InvalidArgumentException
     */
    public function get(string $name)
    {
        if (!isset(self::COLLECTION_FACTORY_CLASSES[$name])) {
            throw new \InvalidArgumentException('Invalid model');
        }

        return $this->objectManager->get(self::COLLECTION_FACTORY_CLASSES[$name]);
    }
}
