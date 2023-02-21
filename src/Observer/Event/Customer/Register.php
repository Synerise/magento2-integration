<?php

namespace Synerise\Integration\Observer\Event\Customer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\ValidatorException;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\EventClientAction;

class Register extends AbstractCustomerEvent implements ObserverInterface
{
    const EVENT = 'customer_register_success';

    public function execute(Observer $observer)
    {
        if (!$this->isLiveEventTrackingEnabled(self::EVENT)) {
            return;
        }

        if ($this->identityHelper->isAdminStore()) {
            return;
        }

        try {
            /** @var \Magento\Customer\Model\Data\Customer $customer */
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

            $this->sendClientRegisteredEvent(
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
    public function sendClientRegisteredEvent(EventClientAction $request, ?int $storeId = null): array
    {
        return $this->getDefaultApiInstance($storeId)
            ->clientRegisteredWithHttpInfo('4.4', $request);
    }
}
