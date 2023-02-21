<?php

namespace Synerise\Integration\Observer\Event\Customer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\ValidatorException;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\EventClientAction;

class Logout  extends AbstractCustomerEvent implements ObserverInterface
{
    const EVENT = 'customer_logout';

    public function execute(Observer $observer)
    {
        if (!$this->isLiveEventTrackingEnabled(self::EVENT)) {
            return;
        }

        if ($this->identityHelper->isAdminStore()) {
            return;
        }

        try {
            $this->sendClientLoggedOutEvent(
                $this->clientHelper->prepareEventClientActionRequest(
                    self::EVENT,
                    $observer->getEvent()->getCustomer(),
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
    public function sendClientLoggedOutEvent(EventClientAction $request, ?int $storeId = null): array
    {
        return $this->getDefaultApiInstance($storeId)
            ->clientLoggedOutWithHttpInfo('4.4', $request);
    }
}
