<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Helper\Api;

abstract class AbstractObserver implements ObserverInterface
{
    const XML_PATH_EVENT_TRACKING_ENABLED = 'synerise/event_tracking/enabled';

    const XML_PATH_EVENT_TRACKING_EVENTS = 'synerise/event_tracking/events';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var LoggerInterface
     */
    protected $logger;


    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    abstract public function execute(Observer $observer);

    /**
     * Is the page tracking enabled.
     *
     * @return bool
     */
    protected function isEventTrackingEnabled($event = null, $storeId = null)
    {
        if (!$this->scopeConfig->isSetFlag(
            self::XML_PATH_EVENT_TRACKING_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        )) {
            return false;
        }

        if (!$event) {
            return true;
        }

        $events = explode(',', $this->scopeConfig->getValue(
            self::XML_PATH_EVENT_TRACKING_EVENTS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));

        return in_array($event, $events);
    }

    /**
     * Is the live event tracking enabled.
     *
     * @return bool
     */
    protected function isLiveEventTrackingEnabled($event = null, $storeId = null)
    {
        if (!$this->isApiKeySet()) {
            return false;
        }

        return $this->isEventTrackingEnabled($event, $storeId);
    }

    /**
     * Checks if Api Key is set for store scope
     *
     * @return bool
     */
    protected function isApiKeySet()
    {
        return $this->scopeConfig->isSetFlag(
            Api::XML_PATH_API_KEY,
            ScopeInterface::SCOPE_STORE
        );
    }
}
