<?php

namespace Synerise\Integration\Helper\Event;

use Exception;
use Magento\Framework\Exception\ValidatorException;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\Client;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;
use Synerise\ApiClient\Model\CustomeventRequest;
use Synerise\Integration\Helper\Api;
use Synerise\Integration\Helper\Data\Context as ContextHelper;
use Synerise\Integration\Model\Config\Source\EventTracking\Events;

class AbstractEvent
{
    /**
     * @var Api
     */
    protected $apiHelper;

    /**
     * @var ContextHelper
     */
    protected $contextHelper;

    public function __construct(
        Api $apiHelper,
        ContextHelper $contextHelper
    ) {
        $this->apiHelper = $apiHelper;
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

    /**
     * @param CustomeventRequest $request
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     * @throws ApiException
     * @throws ValidatorException
     */
    public function sendCustomEvent(CustomeventRequest $request): array
    {
        return $this->apiHelper->getDefaultApiInstance()
            ->customEventWithHttpInfo('4.4', $request);
    }

    /**
     * @param CreateaClientinCRMRequest $createAClientInCrmRequest
     * @param $storeId
     * @return array
     * @throws ApiException
     * @throws ValidatorException
     */
    public function sendCreateClient(CreateaClientinCRMRequest $createAClientInCrmRequest, $storeId = null): array
    {
        return $this->apiHelper->getDefaultApiInstance($storeId)
            ->createAClientInCrmWithHttpInfo('4.4', $createAClientInCrmRequest);
    }
}