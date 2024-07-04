<?php

namespace Synerise\Integration\SyneriseApi\Mapper\Event;

use Synerise\ApiClient\Model\Client;
use Synerise\ApiClient\Model\CustomeventRequest;
use Synerise\Integration\Helper\Tracking\Context;

class CustomerDelete
{
    /**
     * @var Context
     */
    protected $contextHelper;

    /**
     * @param Context $contextHelper
     */
    public function __construct(
        Context $contextHelper
    ) {
        $this->contextHelper = $contextHelper;
    }

    /**
     * Prepare customer delete event by email
     *
     * @param string $email
     * @return CustomeventRequest
     */
    public function prepareRequest(
        string $email
    ): CustomeventRequest {
        $params = $this->contextHelper->prepareContextParams();

        return new CustomeventRequest([
            'event_salt' => $this->contextHelper->generateEventSalt(),
            'time' => $this->contextHelper->getCurrentTime(),
            'action' => 'client.deleteAccount',
            'label' => 'clientDeleteAccount',
            'client' => new Client(['email' => $email]),
            'params' => $params
        ]);
    }
}
