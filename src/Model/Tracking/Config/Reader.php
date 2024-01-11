<?php
namespace Synerise\Integration\Model\Tracking\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Config\ReaderInterface;
use Magento\Store\Model\ScopeInterface;
use Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\Model\Tracking\Config;

class Reader implements ReaderInterface
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Synchronization
     */
    protected $synchronization;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Synchronization $synchronization
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Synchronization $synchronization
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->synchronization = $synchronization;
    }

    /**
     * Read configuration
     *
     * @param mixed $scope
     * @return array
     */
    public function read($scope = null): array
    {
        $output = [];

        if ($this->synchronization->isEnabledStore($scope) && $this->isEventTrackingEnabled($scope)) {
            $this->addValue($output, Config::XML_PATH_EVENT_TRACKING_ENABLED, true);
            foreach ($this->getEventsSelectedForTracking($scope) as $event) {
                $this->addValue($output, Config::XML_PATH_EVENT_TRACKING_EVENTS . '/' . $event, true);
            }

            if ($this->isEventMessageQueueEnabled($scope)) {
                $this->addValue($output, Config::XML_PATH_QUEUE_ENABLED, true);
                foreach ($this->getEventsSelectedForMessageQueue($scope) as $event) {
                    $this->addValue($output, Config::XML_PATH_QUEUE_EVENTS . '/' . $event, true);
                }
            }
        }

        return [$scope => $output];
    }

    /**
     * Check if event should be tracked.
     *
     * @param int $storeId
     * @return bool
     */
    public function isEventTrackingEnabled(int $storeId): bool
    {
        return ($this->scopeConfig->isSetFlag(
            Config::XML_PATH_EVENT_TRACKING_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));
    }

    /**
     * Get na array of events to be tracked
     *
     * @param int $storeId
     * @return array
     */
    public function getEventsSelectedForTracking(int $storeId): array
    {
        return explode(',', $this->scopeConfig->getValue(
            Config::XML_PATH_EVENT_TRACKING_EVENTS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));
    }

    /**
     * Check if message queue is enabled for events
     *
     * @param int $storeId
     * @return bool
     */
    public function isEventMessageQueueEnabled(int $storeId): bool
    {
        return $this->scopeConfig->isSetFlag(
            Config::XML_PATH_QUEUE_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get na array of events to be sent via Message Queue
     *
     * @param int $storeId
     * @return array
     */
    protected function getEventsSelectedForMessageQueue(int $storeId): array
    {
        return explode(',', $this->scopeConfig->getValue(
            Config::XML_PATH_QUEUE_EVENTS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));
    }

    /**
     * Add config value as array to output
     *
     * @param array $output
     * @param string $path
     * @param mixed $value
     * @return void
     */
    public function addValue(array &$output, string $path, $value)
    {
        $chunks = explode('/', $path ?: '');
        $data = [];
        $element = &$data;

        while ($chunks) {
            $key = array_shift($chunks);
            if ($chunks) {
                $element[$key] = [];
                $element = &$element[$key];
            } else {
                $element[$key] = $value;
            }
        }

        $output = array_merge_recursive($output, $data);
    }
}
