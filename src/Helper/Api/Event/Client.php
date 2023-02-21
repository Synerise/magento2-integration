<?php

namespace Synerise\Integration\Helper\Api\Event;

use Exception;
use Synerise\ApiClient\Model\EventClientAction;

class Client extends AbstractEvent
{
    /**
     * @param $event
     * @param $customer
     * @param $uuid
     * @return EventClientAction
     * @throws Exception
     */
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