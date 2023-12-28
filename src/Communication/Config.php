<?php

namespace Synerise\Integration\Communication;

use Magento\Framework\Communication\ConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\MessageQueue\Consumer\Event;
use Synerise\Integration\MessageQueue\Publisher\Data\AbstractBulk;
use Synerise\Integration\MessageQueue\Publisher\Data\All;
use Synerise\Integration\MessageQueue\Publisher\Data\Batch;
use Synerise\Integration\MessageQueue\Publisher\Data\Item;
use Synerise\Integration\MessageQueue\Publisher\Data\Scheduler;

class Config implements ConfigInterface
{
    const MAX_RETRIES = 3;

    /**
     * @var array
     */
    protected $topics = [];

    /**
     * @var Synchronization
     */
    protected $synchronization;

    public function __construct(Synchronization $synchronization)
    {
        $this->synchronization = $synchronization;

        $this->initData();
    }

    /**
     * @param $topicName
     * @return array|mixed
     * @throws LocalizedException
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
     * @param $topicName
     * @return array|mixed
     * @throws LocalizedException
     */
    public function getTopicHandlers($topicName)
    {
        $topicData = $this->getTopic($topicName);
        return $topicData[self::TOPIC_HANDLERS];
    }

    /**
     * @return array
     */
    public function getTopics()
    {
        return $this->topics;
    }

    /**
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
                "Synerise\\Integration\\MessageQueue\\Consumer\\Event",
                'string',
            );
        }

        if ($this->synchronization->isSynchronizationEnabled()) {
            $topicName = Scheduler::TOPIC_NAME;
            $result[$topicName] = $this->getTopicConfig(
                $topicName,
                "Synerise\\Integration\\MessageQueue\\Consumer\\Data\\MysqlScheduler"
            );

            $topicName =  Item::TOPIC_NAME;
            $result[$topicName] = $this->getTopicConfig(
                $topicName,
                "Synerise\\Integration\\MessageQueue\\Consumer\\Data\\Item",
                "Synerise\\Integration\\MessageQueue\\Message\\Data\\Item"
            );

            $handlerType = "Synerise\\Integration\\MessageQueue\\Consumer\\Data\\Bulk";
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
     * @param string $topicName
     * @param string $handlerType
     * @param string $request
     * @param string $request_type
     * @return array
     */
    protected function getTopicConfig(
        string $topicName,
        string $handlerType,
        string $request = "Magento\\AsynchronousOperations\\Api\\Data\\OperationInterface",
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
}
