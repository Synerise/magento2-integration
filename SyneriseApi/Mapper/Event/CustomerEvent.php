<?php

namespace Synerise\Integration\SyneriseApi\Mapper\Event;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Customer;
use Synerise\ApiClient\Model\Client;
use Synerise\ApiClient\Model\EventClientAction;
use Synerise\Integration\Helper\Tracking\Context;

class CustomerEvent
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
     * Prepare request
     *
     * @param string $event
     * @param CustomerInterface|Customer $customer
     * @param string|null $uuid
     * @return EventClientAction
     * @throws \Exception
     */
    public function prepareRequest(
        string $event,
        $customer,
        ?string $uuid = null
    ) {
        return new EventClientAction([
            'event_salt' => $this->contextHelper->generateEventSalt(),
            'time' => $this->contextHelper->getCurrentTime(),
            'label' => $this->contextHelper->getEventLabel($event),
            'client' => $this->prepareClientData(
                $customer,
                $uuid
            ),
            'params' => $this->contextHelper->prepareContextParams()
        ]);
    }

    /**
     * Prepare client data from customer object
     *
     * @param CustomerInterface|Customer $customer
     * @param null|string $uuid
     * @return Client
     */
    public function prepareClientData($customer, ?string $uuid = null): Client
    {
        return new Client([
            'email' => $customer->getEmail(),
            'customId' => $customer->getId(),
            'uuid' => $uuid
        ]);
    }
}
