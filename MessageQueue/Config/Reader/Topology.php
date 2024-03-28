<?php
namespace Synerise\Integration\MessageQueue\Config\Reader;

use Magento\Framework\Config\ReaderInterface;
use Magento\Framework\MessageQueue\DefaultValueProvider;
use Synerise\Integration\Communication\Config;

class Topology implements ReaderInterface
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

    /**
     * Generate bindings
     *
     * @return array
     */
    private function generateBindings(): array
    {
        $bindings = [];

        foreach ($this->config->getTopics() as $topicName => $topicConfig) {
            $binding = $this->prepareBinding($topicName);
            $bindings[$binding['id']] = $binding;
        }
        return $bindings;
    }

    /**
     * Prepare binding
     *
     * @param string $topic
     * @param bool $isDisabled
     * @param string $destinationType
     * @param string|null $destination
     * @return array
     */
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
