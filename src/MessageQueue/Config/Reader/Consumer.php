<?php
namespace Synerise\Integration\MessageQueue\Config\Reader;

use Magento\Framework\Communication\ConfigInterface;
use Magento\Framework\Config\ReaderInterface;
use Magento\Framework\MessageQueue\ConsumerInterface;
use Magento\Framework\MessageQueue\DefaultValueProvider;
use Synerise\Integration\Communication\Config;
use Synerise\Integration\MessageQueue\Consumer\Data\AmqpScheduler;
use Synerise\Integration\MessageQueue\Publisher\Data\Scheduler;

class Consumer implements ReaderInterface
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
        foreach ($this->config->getTopics() as $topicName => $topicConfig) {
            if ($topicName == Scheduler::TOPIC_NAME && $this->isAmqpConfigured()) {
                $topicConfig['handlers'][ConfigInterface::HANDLER_TYPE] = AmqpScheduler::class;
            }

            $result[$topicName] = $this->getConsumerConfig($topicName, array_values($topicConfig['handlers']));
        }
        return $result;
    }

    /**
     * Get consumer config
     *
     * @param string $consumerName
     * @param array $handlers
     * @param int|null $maxMessages
     * @param int|null $maxIdleTime
     * @param int|null $sleep
     * @param bool|null $onlySpawnWhenMessageAvailable
     * @return array
     */
    protected function getConsumerConfig(
        string $consumerName,
        array $handlers,
        ?int $maxMessages = null,
        ?int $maxIdleTime = null,
        ?int $sleep = null,
        ?bool $onlySpawnWhenMessageAvailable = null
    ): array {
        return [
            'name' => $consumerName,
            'queue' => $consumerName,
            'consumerInstance' => ConsumerInterface::class,
            'handlers' => $handlers,
            'connection' => $this->defaultValueProvider->getConnection(),
            'maxMessages' => $maxMessages,
            'maxIdleTime' => $maxIdleTime,
            'sleep' => $sleep,
            'onlySpawnWhenMessageAvailable' => $onlySpawnWhenMessageAvailable
        ];
    }

    /**
     * Check if AMQP is configured
     *
     * @return bool
     */
    protected function isAmqpConfigured(): bool
    {
        return $this->defaultValueProvider->getConnection() == 'amqp';
    }
}
