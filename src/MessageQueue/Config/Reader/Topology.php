<?php
namespace Synerise\Integration\MessageQueue\Config\Reader;

use Magento\Framework\MessageQueue\DefaultValueProvider;
use Synerise\Integration\Communication\Config;

class Topology implements \Magento\Framework\Config\ReaderInterface
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
        $connection = $this->defaultValueProvider->getConnection();
        $exchangeName = $this->defaultValueProvider->getExchange();
        return [
            "$exchangeName--$connection" => [
                'name' => $exchangeName,
                'type' => 'topic',
                'connection' => $connection,
                'durable' => true,
                'autoDelete' => false,
                'internal' => false,
                'bindings' => $this->generateBindings(),
                'arguments' => [],
            ]
        ];
    }

    private function generateBindings()
    {
        $bindings = [];

        foreach ($this->config->getTopics() as $topicName => $topicConfig) {
            $binding = $this->prepareBinding($topicName);
            $bindings[$binding['id']] = $binding;
        }
        return $bindings;
    }

    private function prepareBinding(
        string $topic,
        bool $isDisabled = false,
        string $destinationType = 'queue',
        ?string $destination = null
    ) {
        $destination = $destination?: $topic;

        return [
            'id' => $destinationType . '--' . $destination . '--' . $topic,
            'destinationType' => $destinationType,
            'destination' => $destination,
            'disabled' => $isDisabled,
            'topic' => $topic,
            'arguments' => []
        ];
    }
}
