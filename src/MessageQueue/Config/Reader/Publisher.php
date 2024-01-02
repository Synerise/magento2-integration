<?php
namespace Synerise\Integration\MessageQueue\Config\Reader;

use Magento\Framework\Config\ReaderInterface;
use Magento\Framework\MessageQueue\DefaultValueProvider;
use Synerise\Integration\Communication\Config;

class Publisher implements ReaderInterface
{
    /**
     * @var DefaultValueProvider
     */
    private $defaultValueProvider;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param DefaultValueProvider $defaultValueProvider
     * @param Config $config
     */
    public function __construct(
        DefaultValueProvider $defaultValueProvider,
        Config $config
    ) {
        $this->defaultValueProvider = $defaultValueProvider;
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
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
