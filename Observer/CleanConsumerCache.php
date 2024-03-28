<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Config\CacheInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CleanConsumerCache implements ObserverInterface
{
    public const MONITORED_PATHS = [
        'synerise/synchronization/enabled',
        'synerise/synchronization/models',
        'synerise/synchronization/stores',
        'synerise/workspace/map',
        'synerise/queue/enabled'
    ];

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @param CacheInterface $cache
     */
    public function __construct(
        CacheInterface $cache
    ) {
        $this->cache = $cache;
    }

    /**
     * Execute
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if (array_intersect(self::MONITORED_PATHS, $observer->getData('changed_paths'))) {
            $this->cache->clean(\Zend_Cache::CLEANING_MODE_MATCHING_TAG, ['SYNERISE']);
            $this->cache->remove('message_queue_consumer_config_cache');
        }
    }
}
