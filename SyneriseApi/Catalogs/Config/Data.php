<?php
namespace Synerise\Integration\SyneriseApi\Catalogs\Config;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Config\CacheInterface;
use Magento\Framework\Serialize\SerializerInterface;

class Data extends \Magento\Framework\Config\Data
{
    /**
     * Additional cache tags
     *
     * @var array
     */
    protected $cacheTags = ['SYNERISE'];

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
        $cacheId = 'synerise_config_api_catalogs',
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
     * @param string $path
     * @param mixed $default
     * @return array|mixed|null
     */
    public function get($path = null, $default = null)
    {
        $this->loadScopedData($path);
        return parent::get($path, $default);
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
     * Clear cache data
     *
     * @param int $scopeId
     * @return void
     */
    public function resetByScopeId(int $scopeId)
    {
        $this->cache->remove($this->getScopedCacheId($scopeId));
        unset($this->loadedScopes[$scopeId]);
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
