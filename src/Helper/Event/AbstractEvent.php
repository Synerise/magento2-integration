<?php

namespace Synerise\Integration\Helper\Event;

use Exception;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\Client;
use Synerise\ApiClient\Model\CustomeventRequest;
use Synerise\Integration\Helper\AbstractDefaultApiAction;
use Synerise\Integration\Helper\Api;
use Synerise\Integration\Helper\Api\DefaultApiFactory;
use Synerise\Integration\Helper\Data\Context as ContextHelper;
use Synerise\Integration\Model\Config\Source\EventTracking\Events;

abstract class AbstractEvent extends AbstractDefaultApiAction
{
    /**
     * @var ContextHelper
     */
    protected $contextHelper;

    public function __construct(
        Api $apiHelper,
        ContextHelper $contextHelper,
        DefaultApiFactory $defaultApiFactory
    ) {
        $this->contextHelper = $contextHelper;

        parent::__construct($apiHelper, $defaultApiFactory);
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

    /**
     * @param CustomeventRequest $request
     * @param int|null $storeId
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     * @throws ApiException
     */
    public function sendCustomEvent(CustomeventRequest $request, ?int $storeId = null): array
    {
        return $this->getDefaultApiInstance($storeId)
            ->customEventWithHttpInfo('4.4', $request);
    }
}