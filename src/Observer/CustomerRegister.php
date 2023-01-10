<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Helper\Identity;
use Synerise\Integration\Helper\Event\Client;

class CustomerRegister extends AbstractObserver implements ObserverInterface
{
    const EVENT = 'customer_register_success';

    /**
     * @var Client
     */
    protected $clientAction;

    /**
     * @var Identity
     */
    protected $identityHelper;


    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        Client $clientAction,
        Identity $identityHelper
    ) {
        $this->clientAction = $clientAction;
        $this->identityHelper = $identityHelper;

        parent::__construct($scopeConfig, $logger);
    }

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
                $this->identityHelper->mergeClients(
                    $customer->getEmail(),
                    $uuid,
                    $this->identityHelper->getClientUuid()
                );
            }

            $this->clientAction->sendClientRegisteredEvent(
                $this->clientAction->prepareEventClientActionRequest(
                    self::EVENT,
                    $customer,
                    $this->identityHelper->getClientUuid()
                )
            );
        } catch (\Exception $e) {
            $this->logger->error('Synerise Api request failed', ['exception' => $e]);
        }
    }
}
