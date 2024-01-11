<?php

namespace Synerise\Integration\Communication;

use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\Framework\Communication\ConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\MessageQueue\DefaultValueProvider;
use Magento\Framework\Phrase;
use Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\MessageQueue\Consumer\Data\AmqpScheduler;
use Synerise\Integration\MessageQueue\Consumer\Data\Bulk;
use Synerise\Integration\MessageQueue\Consumer\Data\MysqlScheduler;
use Synerise\Integration\MessageQueue\Consumer\Event;
use Synerise\Integration\MessageQueue\Publisher\Data\AbstractBulk;
use Synerise\Integration\MessageQueue\Publisher\Data\All;
use Synerise\Integration\MessageQueue\Publisher\Data\Batch;
use Synerise\Integration\MessageQueue\Publisher\Data\Item;
use Synerise\Integration\MessageQueue\Publisher\Data\Scheduler;

class Config implements ConfigInterface
{
    public const MAX_RETRIES = 3;

    /**
     * @var array
     */
    protected $topics = [];

    /**
     * @var DefaultValueProvider
     */
    private $defaultValueProvider;

    /**
     * @var Synchronization
     */
    protected $synchronization;

    /**
     * @param DefaultValueProvider $defaultValueProvider
     * @param Synchronization $synchronization
     */
    public function __construct(
        DefaultValueProvider $defaultValueProvider,
        Synchronization $synchronization
    ) {
        $this->defaultValueProvider = $defaultValueProvider;
        $this->synchronization = $synchronization;

        $this->initData();
    }

    /**
     * @inheritDoc
     */
    public function getTopic($topicName)
    {
        if (!isset($this->topics[$topicName])) {
            throw new LocalizedException(
                new Phrase('Topic "%topic" is not configured.', ['topic' => $topicName])
            );
        }

        return $this->topics[$topicName];
    }

    /**
     * @inheritDoc
     */
    public function getTopicHandlers($topicName)
    {
        $topicData = $this->getTopic($topicName);
        return $topicData[self::TOPIC_HANDLERS];
    }

    /**
     * @inheritDoc
     */
    public function getTopics(): array
    {
        return $this->topics;
    }

    /**
     * Init config data
     *
     * @return void
     */
    private function initData()
    {
        $enabledModels = $this->synchronization->getEnabledModels();
        $enabledStores = $this->synchronization->getEnabledStores();

        $result = [];

        $isEventQueueEnabled = false;
        foreach ($enabledStores as $enabledStore) {
            if ($this->synchronization->isEventQueueEnabled($enabledStore)) {
                $isEventQueueEnabled = true;
            }
        }

        if ($isEventQueueEnabled) {
            $topicName = Event::TOPIC_NAME;
            $result[$topicName] = $this->getTopicConfig(
                $topicName,
                Event::class,
                'string',
            );
        }

        if ($this->synchronization->isSynchronizationEnabled()) {
            $topicName = Scheduler::TOPIC_NAME;
            $result[$topicName] = $this->getTopicConfig(
                $topicName,
                $this->isAmqpConfigured() ? AmqpScheduler::class : MysqlScheduler::class
            );

            $topicName =  Item::TOPIC_NAME;
            $result[$topicName] = $this->getTopicConfig(
                $topicName,
                \Synerise\Integration\MessageQueue\Consumer\Data\Item::class,
                \Synerise\Integration\MessageQueue\Message\Data\Item::class
            );

            $handlerType = Bulk::class;
            foreach ($enabledModels as $model) {
                foreach ($enabledStores as $storeId) {
                    $topicName = AbstractBulk::getTopicName($model, Batch::TYPE, $storeId);
                    $result[$topicName] = $this->getTopicConfig(
                        $topicName,
                        $handlerType
                    );

                    $topicName = AbstractBulk::getTopicName($model, All::TYPE, $storeId);
                    $result[$topicName] = $this->getTopicConfig(
                        $topicName,
                        $handlerType
                    );
                }
            }

        }

        $this->topics = $result;
    }

    /**
     * Get topic config
     *
     * @param string $topicName
     * @param string $handlerType
     * @param string $request
     * @param string $request_type
     * @return array
     */
    protected function getTopicConfig(
        string $topicName,
        string $handlerType,
        string $request = OperationInterface::class,
        string $request_type = 'object_interface'
    ): array {
        return [
            self::TOPIC_NAME => $topicName,
            self::TOPIC_IS_SYNCHRONOUS => false,
            self::TOPIC_REQUEST => $request,
            self::TOPIC_REQUEST_TYPE => $request_type,
            self::TOPIC_RESPONSE => null,
            self::TOPIC_HANDLERS => [
                $topicName => [
                    self::HANDLER_TYPE => $handlerType,
                    self::HANDLER_METHOD => 'process'
                ]
            ]
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
