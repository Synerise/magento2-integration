<?php

namespace Synerise\Integration\Helper\Event;

use Synerise\ApiClient\Model\EventClientAction;

class Client extends AbstractEvent
{

    public function sendClientLoginEvent(EventClientAction $request) {
        return $this->apiHelper->getDefaultApiInstance()
            ->clientLoggedInWithHttpInfo('4.4', $request);
    }

    public function sendClientLoggedOutEvent(EventClientAction $request) {
        return $this->apiHelper->getDefaultApiInstance()
            ->clientLoggedOutWithHttpInfo('4.4', $request);
    }

    public function sendClientRegisteredEvent(EventClientAction $request) {
        return $this->apiHelper->getDefaultApiInstance()
            ->clientRegisteredWithHttpInfo('4.4', $request);
    }

    public function prepareEventClientActionRequest($event, $customer = null, $uuid = null): EventClientAction {
        return new EventClientAction(
            $this->prepareEventData(
                $this->getEventLabel($event),
                new \Synerise\ApiClient\Model\Client([
                    'uuid' => $uuid,
                    'email' => $customer ? $customer->getEmail() : null,
                    'custom_id' => $customer ? $customer->getId() : null
                ])
            )
        );
    }
}