<?php
namespace Synerise\Integration\MessageQueue\Config\Reader;

use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory as ConfigCollectionFactory;
use Magento\Framework\Config\ReaderInterface;
use Magento\Framework\MessageQueue\ConsumerInterface;
use Magento\Framework\MessageQueue\DefaultValueProvider;
use Synerise\Integration\Communication\Config as CommunicationConfig;
use Synerise\Integration\MessageQueue\Consumer\Event;
use Synerise\Integration\MessageQueue\Publisher\Data\Item;
use Synerise\Integration\MessageQueue\Publisher\Data\Scheduler;
use Synerise\Integration\Model\Synchronization\Config as SynchronizationConfig;

class Consumer implements ReaderInterface
{
    /**
     * @var DefaultValueProvider
     */
    private $defaultValueProvider;

    /**
     * @var ConfigCollectionFactory
     */
    private $collectionFactory;

    /**
     * @var CommunicationConfig
     */
    private $communicationConfig;

    /**
     * @var SynchronizationConfig
     */
    private $synchronizationConfig;

    /**
     * @param DefaultValueProvider $defaultValueProvider
     * @param ConfigCollectionFactory $collectionFactory
     * @param CommunicationConfig $communicationConfig
     * @param SynchronizationConfig $synchronizationConfig
     */
    public function __construct(
        DefaultValueProvider $defaultValueProvider,
        ConfigCollectionFactory $collectionFactory,
        CommunicationConfig $communicationConfig,
        SynchronizationConfig $synchronizationConfig
    ) {
        $this->defaultValueProvider = $defaultValueProvider;
        $this->collectionFactory = $collectionFactory;
        $this->communicationConfig = $communicationConfig;
        $this->synchronizationConfig = $synchronizationConfig;
    }

    /**
     * @inheritDoc
     */
    public function read($scope = null)
    {
        $result = [];
        if ($this->synchronizationConfig->isStoreConfigured()) {
            foreach ($this->communicationConfig->getTopics() as $topicName => $topicConfig) {
                if ($this->isAvailable($topicName)) {
                    $result[$topicName] = $this->getConsumerConfig($topicName, array_values($topicConfig['handlers']));
                }
            }
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
     * Check if consumer should be configured fo specific topic
     *
     * @param string $topicName
     * @return bool
     */
    protected function isAvailable(string $topicName)
    {
        if ($topicName == Event::TOPIC_NAME) {
            return $this->isEventQueueEnabled();
        } elseif ($topicName == Scheduler::TOPIC_NAME || $topicName == Item::TOPIC_NAME) {
            return $this->isSynchronizationEnabled();
        } else {
            $topicNameSegments = explode('.', $topicName);
            return $this->isSynchronizationEnabled($topicNameSegments[4], $topicNameSegments[5]);
        }
    }

    /**
     * Check if event consumer should be configured
     *
     * @return bool
     */
    protected function isEventQueueEnabled()
    {
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter('value', 1)
            ->addFieldToFilter('path', 'synerise/queue/enabled');
        return (bool) $collection->getSize();
    }

    /**
     * Check if synchronization consumer should be configured
     *
     * @param string|null $model
     * @param int|null $storeId
     * @return bool
     */
    private function isSynchronizationEnabled(?string $model = null, ?int $storeId = null): bool
    {
        if (!$this->synchronizationConfig->isEnabled()) {
            return false;
        }

        if ($storeId && !$this->synchronizationConfig->isStoreConfigured($storeId)) {
            return false;
        }

        if ($model && !$this->synchronizationConfig->isModelEnabled($model)) {
            return false;
        }

        return true;
    }
}
