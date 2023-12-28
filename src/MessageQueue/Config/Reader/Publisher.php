<?php
namespace Synerise\Integration\MessageQueue\Config\Reader;

use Magento\Framework\MessageQueue\ConsumerInterface;
use Magento\Framework\MessageQueue\DefaultValueProvider;
use Synerise\Integration\Communication\Config;

class Publisher implements \Magento\Framework\Config\ReaderInterface
{
    /**
     * @var DefaultValueProvider
     */
    private $defaultValueProvider;

    /**
     * @var Config
     */
    private $config;

    public function __construct(
        DefaultValueProvider $defaultValueProvider,
        Config $config
    ) {
        $this->defaultValueProvider = $defaultValueProvider;
        $this->config = $config;
    }

    public function read($scope = null)
    {
        $result = [];

        $connections[$this->defaultValueProvider->getConnection()] = [
            'name' => $this->defaultValueProvider->getConnection(),
            'exchange' => $this->defaultValueProvider->getExchange(),
            'disabled' => false
        ];

        foreach ($this->config->getTopics() as $topic => $topicConfig) {
            $result[$topic] = [
                'topic' => $topic,
                'disabled' => false,
                'connections' => $connections,
            ];
        }

        return $result;
    }
}
