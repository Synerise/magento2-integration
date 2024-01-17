<?php
namespace Synerise\Integration\Model\Workspace\Config;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Config\CacheInterface;
use Magento\Framework\Serialize\SerializerInterface;

class Data extends \Magento\Framework\Config\Data
{
    /**
     * @inheirtDoc
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
     * @param string $cacheId
     * @param SerializerInterface|null $serializer
     * @param int|null $storeId
     */
    public function __construct(
        Reader $reader,
        CacheInterface $cache,
        $cacheId = 'synerise_config_workspace',
        ?SerializerInterface $serializer = null,
        $storeId = null
    ) {
        $this->storeId = $storeId;

        $this->reader = $reader;
        $this->cache = $cache;
        $this->cacheId = $cacheId;
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(SerializerInterface::class);

        parent::__construct($reader, $cache, $cacheId, $serializer);
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
            $data = $this->reader->read();
            $this->cache->save($this->serializer->serialize($data), $this->cacheId, $this->cacheTags);
        } else {
            $data = $this->serializer->unserialize($data);
        }

        $this->_data = $data[$this->storeId] ?? [];
    }
}
