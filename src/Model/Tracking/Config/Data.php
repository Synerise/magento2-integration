<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Synerise\Integration\Model\Tracking\Config;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Config\CacheInterface;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Provides country postcodes configuration
 */
class Data extends \Magento\Framework\Config\Data
{
    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var string|null
     */
    private $cacheId;

    /**
     * @var SerializerInterface|null
     */
    private $serializer;

    /**
     * Loaded scopes array
     *
     * @var array
     */
    protected $loadedScopes = [];

    /**
     * Constructor
     *
     * @param Reader $reader
     * @param CacheInterface $cache
     * @param string $cacheId
     * @param SerializerInterface|null $serializer
     */
    public function __construct(
        Reader $reader,
        CacheInterface $cache,
        $cacheId = 'synerise_event_tracking_config',
        ?SerializerInterface $serializer = null
    ) {
        $this->reader = $reader;
        $this->cache = $cache;
        $this->cacheId = $cacheId;
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(SerializerInterface::class);
    }

    /**
     * Get config value by key
     *
     * @param string|int $scope
     * @param string|null $path
     * @param mixed $default
     * @return array|mixed|null
     */
    public function getByScope($scope, string $path = null, $default = null)
    {
        $this->loadScopedData($scope);
        return parent::get($scope . '/' .$path, $default);
    }

    /**
     * Load data for selected scope
     *
     * @param int $scopeId
     * @return void
     */
    protected function loadScopedData(int $scopeId)
    {
        $cacheId = $this->getScopedCacheId($scopeId);
        if (!isset($this->loadedScopes[$scopeId])) {
            if ($data = $this->cache->load($cacheId)
            ) {
                $data = $this->serializer->unserialize($data);
            } else {
                $data = $this->reader->read($scopeId);
                $this->cache->save($this->serializer->serialize($data), $cacheId, $this->cacheTags);

            }
            $this->merge($data);
            $this->loadedScopes[$scopeId] = true;
        }
    }

    /**
     * Get scoped cache ID
     *
     * @param int $scopeId
     * @return string
     */
    protected function getScopedCacheId(int $scopeId): string
    {
        return "{$this->cacheId}_{$scopeId}";
    }
}
