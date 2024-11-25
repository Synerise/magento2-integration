<?php
namespace Synerise\Integration\Search\Attributes\Config;

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
     * @param Reader $reader
     * @param CacheInterface $cache
     * @param string $cacheId
     * @param SerializerInterface|null $serializer
     */
    public function __construct(
        Reader $reader,
        CacheInterface $cache,
        $cacheId = 'synerise_search_filters',
        ?SerializerInterface $serializer = null
    ) {
        parent::__construct($reader, $cache, $cacheId, $serializer);
    }
}
