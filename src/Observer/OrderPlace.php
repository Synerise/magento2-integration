<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Helper\Identity;
use Synerise\Integration\Helper\Update\Order;

class OrderPlace  extends AbstractObserver implements ObserverInterface
{
    const EVENT = 'sales_order_place_after';

    /**
     * @var Identity
     */
    protected $identityHelper;

    /**
     * @var Order
     */
    protected $orderHelper;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        Identity $identityHelper,
        Order $orderHelper
    ) {
        $this->identityHelper = $identityHelper;
        $this->orderHelper = $orderHelper;

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

            $this->orderHelper->sendCreateTransaction(
                $this->orderHelper->prepareCreateTransactionRequest(
                    $order,
                    $this->identityHelper->getClientUuid()
                ),
                $order->getStoreId()
            );

            $this->orderHelper->markItemsAsSent([$order->getEntityId()]);

            if (!$this->identityHelper->isAdminStore() && $order->getCustomerIsGuest()) {
                $this->orderHelper->sendCreateClient(
                    $this->orderHelper->prepareCreateClientRequest(
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
