<?php

namespace Synerise\Integration\Observer\Event\Customer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\ValidatorException;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\EventClientAction;

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
            /** @var \Magento\Customer\Model\Customer $customer */
            $customer = $observer->getEvent()->getCustomer();

            $uuid = $this->identityHelper->getClientUuid();
            if ($uuid && $this->identityHelper->manageClientUuid($uuid, $customer->getEmail())) {
                $this->sendMergeClients(
                    $this->identityHelper->prepareMergeClientsRequest(
                        $customer->getEmail(),
                        $uuid,
                        $this->identityHelper->getClientUuid()
                    )
                );
            }
            
            $this->sendClientLoginEvent(
                $this->clientHelper->prepareEventClientActionRequest(
                    self::EVENT,
                    $customer,
                    $this->identityHelper->getClientUuid()
                )
            );
        } catch (\Exception $e) {
            $this->logger->error('Synerise Api request failed', ['exception' => $e]);
        }
    }

    /**
     * @param EventClientAction $request
     * @param int|null $storeId
     * @return array
     * @throws ValidatorException
     * @throws ApiException
     */
    public function sendClientLoginEvent(EventClientAction $request, ?int $storeId = null): array
    {
        return $this->getDefaultApiInstance($storeId)
            ->clientLoggedInWithHttpInfo('4.4', $request);
    }
}