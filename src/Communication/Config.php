<?php

namespace Synerise\Integration\Communication;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Communication\ConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Synerise\Integration\Helper\Synchronization;

class Config implements ConfigInterface
{
    /**
     * @var array 
     */
    protected $topics = [];

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;

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
        return $topicData[self::TOPIC_HANDLERS];    }

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
        $enabledModels = $this->getEnabledModels();
        $enabledStores = $this->getEnabledStores();

        $result = [];

        $topicName = 'synerise.queue.events';
        $result[$topicName] = $this->getTopicConfig(
            $topicName,
            "Synerise\\Integration\\MessageQueue\\Consumer\\Event",
            'string',
        );

        $topicName = 'synerise.queue.data.scheduler';
        $result[$topicName] = $this->getTopicConfig(
            $topicName,
            "Synerise\\Integration\\MessageQueue\\Consumer\\Data\\Scheduler"
        );

        $topicName =  "synerise.queue.data.item";
        $result[$topicName] = $this->getTopicConfig(
            $topicName,
            "Synerise\\Integration\\MessageQueue\\Consumer\\Data\\Item",
            "Synerise\\Integration\\MessageQueue\\Message\\Data\\Item"
        );

        foreach($enabledModels as $model) {
            foreach($enabledStores as $storeId) {
                $topicName = $this->getDataTopicName($model, 'batch', $storeId);
                $result[$topicName] = $this->getTopicConfig(
                    $topicName,
                    "Synerise\\Integration\\MessageQueue\\Consumer\\Data\\Batch\\" . ucfirst($model)
                );

                $topicName = $this->getDataTopicName($model, 'range', $storeId);
                $result[$topicName] = $this->getTopicConfig(
                    $topicName,
                    "Synerise\\Integration\\MessageQueue\\Consumer\\Data\\Range\\" . ucfirst($model)
                );
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
    ): array
    {
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
     * @param string $model
     * @param string $type
     * @param int|null $storeId
     * @return string
     */
    protected function getDataTopicName(string $model, string $type, ?int $storeId = null): string
    {
        $topicName =  "synerise.queue.data.$model.$type";
        return $storeId ? "$topicName.$storeId" : "$topicName";
    }

    /**
     * @return string[]
     */
    protected function getEnabledModels(): array
    {
        $enabledModels = $this->scopeConfig->getValue(Synchronization::XML_PATH_SYNCHRONIZATION_MODELS);
        return !empty($enabledModels) ? explode(',', $enabledModels) : [];
    }

    /**
     * @return string[]
     */
    protected function getEnabledStores(): array
    {
        $enabledStores = $this->scopeConfig->getValue(Synchronization::XML_PATH_SYNCHRONIZATION_STORES);
        return !empty($enabledStores) ? explode(',', $enabledStores) : [];
    }
}