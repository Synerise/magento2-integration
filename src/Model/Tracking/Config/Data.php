<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Synerise\Integration\Model\Tracking\Config;

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
     * Loaded scopes
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
     * @param SerializerInterface $serializer
     */
    public function __construct(
        Reader $reader,
        CacheInterface $cache,
        SerializerInterface $serializer,
        $cacheId = 'synerise_event_tracking_config'
    ) {
        $this->reader = $reader;
        $this->cache = $cache;
        $this->cacheId = $cacheId;
        $this->serializer = $serializer;
    }

    /**
     * Get config value by key
     *
     * @param string $path
     * @param mixed $default
     * @return array|mixed|null
     */
    public function getByScope($scope, $path = null, $default = null)
    {
        $this->loadScopedData($scope);
        return parent::get($scope . '/' .$path, $default);
    }

    /**
     * Load data for selected scope
     *
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
