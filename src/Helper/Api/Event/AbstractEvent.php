<?php

namespace Synerise\Integration\Helper\Api\Event;

use Exception;
use Synerise\ApiClient\Model\Client;
use Synerise\Integration\Helper\Api\Context as ContextHelper;
use Synerise\Integration\Model\Config\Source\EventTracking\Events;
abstract class AbstractEvent
{
    /**
     * @var ContextHelper
     */
    protected $contextHelper;

    public function __construct(
        ContextHelper $contextHelper
    ) {
        $this->contextHelper = $contextHelper;
    }

    /**
     * @param string $label
     * @param Client $client
     * @param array|null $params
     * @param string|null $action
     * @return array
     */
    public function prepareEventData(string $label, Client $client, ?array $params = [], ?string $action = null): array
    {
        $params['applicationName'] = $this->contextHelper->getApplicationName();
        $params['source'] = $this->contextHelper->getSource();
        $params['storeId'] = $this->contextHelper->getStoreId();
        $params['storeUrl'] = $this->contextHelper->getStoreBaseUrl();

        return [
            'time' => $this->contextHelper->getCurrentTime(),
            'action' => $action,
            'label' => $label,
            'client' => $client,
            'params' => $params
        ];
    }

    /**
     * @param string $event
     * @return string
     * @throws Exception
     */
    public function getEventLabel(string $event): string
    {
        if (!Events::OPTIONS[$event]) {
            throw new Exception('Invalid event');
        }

        return Events::OPTIONS[$event];
    }
}