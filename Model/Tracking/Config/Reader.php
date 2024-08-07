<?php
namespace Synerise\Integration\Model\Tracking\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Config\ReaderInterface;
use Magento\Store\Model\ScopeInterface;
use Synerise\Integration\Model\Tracking\Config;

class Reader implements ReaderInterface
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Synerise\Integration\Model\Synchronization\Config
     */
    protected $synchronization;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param \Synerise\Integration\Model\Synchronization\Config $synchronization
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        \Synerise\Integration\Model\Synchronization\Config $synchronization
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

        if ($this->synchronization->isStoreConfigured($scope) && $this->isEventTrackingEnabled($scope)) {
            $output['enabled'] = true;
            $output['events'] = [];

            foreach ($this->getEventsSelectedForTracking($scope) as $event) {
                $output['events'][] = $event;
            }

            if ($this->isEventMessageQueueEnabled($scope)) {
                $output['queue_enabled'] = true;
                $output['queue_events'] = [];
                foreach ($this->getEventsSelectedForMessageQueue($scope) as $event) {
                    $output['queue_events'][] = $event;
                }
            }

            $output['customer_delete_behavior'] = $this->getCustomerDeleteBehavior($scope);
        }

        return $output;
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
        return explode(',', (string) $this->scopeConfig->getValue(
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
        return explode(',', (string) $this->scopeConfig->getValue(
            Config::XML_PATH_QUEUE_EVENTS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));
    }


    /**
     * Get customer delete behavior
     *
     * @param int $storeId
     * @return string
     */
    public function getCustomerDeleteBehavior(int $storeId): string
    {
        return $this->scopeConfig->getValue(
            Config::XML_PATH_CUSTOMER_DELETE_BEHAVIOR,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
