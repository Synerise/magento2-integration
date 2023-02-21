<?php

namespace Synerise\Integration\Helper\Api\Update;

use Magento\Newsletter\Model\Subscriber;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;
use Synerise\Integration\Helper\Api\Identity;

class ClientAgreement
{
    /**
     * @param Subscriber $subscriber
     * @return CreateaClientinCRMRequest
     */
    public function prepareSubscribeRequest(Subscriber $subscriber): CreateaClientinCRMRequest
    {
        $email = $subscriber->getSubscriberEmail();
        return new CreateaClientinCRMRequest(
            [
                'email' => $email,
                'uuid' => Identity::generateUuidByEmail($email),
                'agreements' => [
                    'email' => $subscriber->getSubscriberStatus() == Subscriber::STATUS_SUBSCRIBED ? 1 : 0
                ]
            ]
        );
    }

    /**
     * @param Subscriber $subscriber
     * @return CreateaClientinCRMRequest
     */
    public function prepareUnsubscribeRequest(Subscriber $subscriber): CreateaClientinCRMRequest
    {
        $email = $subscriber->getSubscriberEmail();
        return new CreateaClientinCRMRequest(
            [
                'email' => $email,
                'uuid' => Identity::generateUuidByEmail($email),
                'agreements' => [
                    'email' =>  0
                ]
            ]
        );
    }
}
