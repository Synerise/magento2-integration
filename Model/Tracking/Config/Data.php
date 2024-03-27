<?php
namespace Synerise\Integration\Model\Tracking\Config;

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
     * @var int
     */
    private $storeId;

    /**
     * @param Reader $reader
     * @param CacheInterface $cache
     * @param integer $storeId
     * @param string $cacheId
     * @param SerializerInterface|null $serializer
     */
    public function __construct(
        Reader $reader,
        CacheInterface $cache,
        $storeId,
        $cacheId = 'synerise_config_event_tracking',
        ?SerializerInterface $serializer = null
    ) {
        $this->storeId = $storeId;
        $this->reader = $reader;
        $this->cache = $cache;
        $this->cacheId = $cacheId . '_' . $storeId;
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(SerializerInterface::class);

        parent::__construct($reader, $cache, $cacheId . '_' . $storeId, $serializer);
    }

    /**
     * Initialise data for configuration
     *
     * @return void
     */
    protected function initData()
    {
        $data = $this->cache->load($this->cacheId);
        if (false === $data) {
            $data = $this->reader->read($this->storeId);
            $this->cache->save($this->serializer->serialize($data), $this->cacheId, $this->cacheTags);
        } else {
            $data = $this->serializer->unserialize($data);
        }

        $this->merge($data);
    }
}
