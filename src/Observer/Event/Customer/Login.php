<?php

namespace Synerise\Integration\Observer\Event\Customer;

use Magento\Customer\Model\Customer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class Login extends AbstractCustomerEvent implements ObserverInterface
{
    const EVENT = 'customer_login';

    public function execute(Observer $observer)
    {
        if (!$this->isLiveEventTrackingEnabled(self::EVENT)) {
            return;
        }

        if ($this->identityHelper->isAdminStore()) {
            return;
        }

        try {
            /** @var Customer $customer */
            $customer = $observer->getEvent()->getCustomer();

            $uuid = $this->identityHelper->getClientUuid();
            if ($uuid && $this->identityHelper->manageClientUuid($uuid, $customer->getEmail())) {
                $request = $this->identityHelper->prepareMergeClientsRequest(
                    $customer->getEmail(),
                    $uuid,
                    $this->identityHelper->getClientUuid()
                );

                $this->publishOrSendClientMerge($request, $customer->getStoreId());
            }
            
            $request = $this->clientHelper->prepareEventClientActionRequest(
                self::EVENT,
                $customer,
                $this->identityHelper->getClientUuid()
            );

            $this->publishOrSendEvent(static::EVENT, $request, $customer->getStoreId());
        } catch (\Exception $e) {
            $this->logger->error('Synerise Error', ['exception' => $e]);
        }
    }
}