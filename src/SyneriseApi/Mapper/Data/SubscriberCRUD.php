<?php

namespace Synerise\Integration\SyneriseApi\Mapper\Data;

use Magento\Newsletter\Model\Subscriber;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;
use Synerise\Integration\Helper\Tracking\UuidGenerator;

class SubscriberCRUD
{
    /**
     * @var UuidGenerator
     */
    protected $uuidGenerator;

    /**
     * @param UuidGenerator $uuidGenerator
     */
    public function __construct(UuidGenerator $uuidGenerator)
    {
        $this->uuidGenerator = $uuidGenerator;
    }

    /**
     * Prepare request from subscription
     *
     * @param Subscriber $subscriber
     * @param bool $unsubscribe
     * @return CreateaClientinCRMRequest
     */
    public function prepareRequest(Subscriber $subscriber, bool $unsubscribe = false): CreateaClientinCRMRequest
    {
        $email = $subscriber->getSubscriberEmail();
        return new CreateaClientinCRMRequest(
            [
                'email' => $email,
                'uuid' => $this->uuidGenerator->generateByEmail($email),
                'agreements' => [
                    'email' => !$unsubscribe && $this->isSubscribed($subscriber) ? 1 : 0
                ]
            ]
        );
    }

    /**
     * Check if subscriber is subscribed
     *
     * @param Subscriber $subscriber
     * @return bool
     */
    protected function isSubscribed(Subscriber $subscriber)
    {
        return $subscriber->getSubscriberStatus() == Subscriber::STATUS_SUBSCRIBED;
    }
}
