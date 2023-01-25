<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Helper\Identity;
use Synerise\Integration\Helper\Update\Transaction;

class OrderPlace  extends AbstractObserver implements ObserverInterface
{
    const EVENT = 'sales_order_place_after';

    /**
     * @var Identity
     */
    protected $identityHelper;

    /**
     * @var Transaction
     */
    protected $transactionHelper;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        Identity $identityHelper,
        Transaction $transactionHelper
    ) {
        $this->identityHelper = $identityHelper;
        $this->transactionHelper = $transactionHelper;

        parent::__construct($scopeConfig, $logger);
    }

    public function execute(Observer $observer)
    {
        if (!$this->isEventTrackingEnabled(self::EVENT)) {
            return;
        }

        try {
            /** @var \Magento\Sales\Model\Order $order */
            $order = $observer->getEvent()->getOrder();

            $uuid = $this->identityHelper->getClientUuid();
            if ($uuid && $this->identityHelper->manageClientUuid($uuid, $order->getCustomerEmail())) {
                $this->identityHelper->mergeClients(
                    $order->getCustomerEmail(),
                    $uuid,
                    $this->identityHelper->getClientUuid()
                );
            }

            $this->transactionHelper->sendCreateTransaction(
                $this->transactionHelper->prepareCreateTransactionRequest(
                    $order,
                    $this->identityHelper->getClientUuid()
                ),
                $order->getStoreId()
            );

            $this->transactionHelper->markAsSent([$order->getEntityId()]);

            if (!$this->identityHelper->isAdminStore() && $order->getCustomerIsGuest()) {
                $this->transactionHelper->sendCreateClient(
                    $this->transactionHelper->prepareCreateClientRequest(
                        $order,
                        $this->identityHelper->getClientUuid()
                    ),
                    $order->getStoreId()
                );
            }

        } catch (\Exception $e) {
            $this->logger->error('Synerise Api request failed', ['exception' => $e]);
        }
    }
}
