<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\Helper\Tracking\UuidManagement;
use Synerise\Integration\MessageQueue\Publisher\Data\Item as DataItemPublisher;
use Synerise\Integration\MessageQueue\Publisher\Event as EventPublisher;
use Synerise\Integration\Model\Synchronization\Config;
use Synerise\Integration\SyneriseApi\Sender\Data\Order as OrderSender;
use Synerise\Integration\SyneriseApi\Sender\Data\Customer as CustomerSender;

class OrderPlace implements ObserverInterface
{
    public const EVENT = 'sales_order_place_after';

    public const CUSTOMER_UPDATE = 'customer_update_guest_order';

    /**
     * @var DataItemPublisher
     */
    protected $dataItemPublisher;

    /**
     * @var EventPublisher
     */
    protected $eventPublisher;

    /**
     * @var OrderSender
     */
    protected $orderSender;

    /**
     * @var CustomerSender
     */
    protected $customerSender;

    /**
     * @var Config
     */
    protected $synchronization;

    /**
     * @var Logger
     */
    protected $loggerHelper;

    /**
     * @var Tracking
     */
    protected $trackingHelper;

    /**
     * @var UuidManagement
     */
    protected $uuidHelper;

    /**
     * @param DataItemPublisher $dataItemPublisher
     * @param EventPublisher $eventPublisher
     * @param OrderSender $orderSender
     * @param CustomerSender $customerSender
     * @param Logger $loggerHelper
     * @param Config $synchronization
     * @param Tracking $trackingHelper
     * @param UuidManagement $uuidHelper
     */
    public function __construct(
        DataItemPublisher $dataItemPublisher,
        EventPublisher $eventPublisher,
        OrderSender $orderSender,
        CustomerSender $customerSender,
        Logger $loggerHelper,
        Config $synchronization,
        Tracking $trackingHelper,
        UuidManagement $uuidHelper
    ) {
        $this->dataItemPublisher = $dataItemPublisher;
        $this->eventPublisher = $eventPublisher;
        $this->orderSender = $orderSender;
        $this->customerSender = $customerSender;
        $this->loggerHelper = $loggerHelper;
        $this->synchronization = $synchronization;
        $this->trackingHelper = $trackingHelper;
        $this->uuidHelper = $uuidHelper;
    }

    /**
     * Execute
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            /** @var Order $order */
            $order = $observer->getEvent()->getOrder();
            $storeId = $order->getStoreId();

            if (!$this->trackingHelper->isEventTrackingAvailable(self::EVENT, $storeId)) {
                return;
            }

            if (!$this->trackingHelper->getContext()->isAdminStore()) {
                $this->uuidHelper->manageByEmail(
                    $order->getCustomerEmail(),
                    $this->trackingHelper->getContext()->getStoreId()
                );
            }

            if (!$this->synchronization->isModelEnabled(OrderSender::MODEL)) {
                return;
            }

            $guestCustomerRequest = $this->prepareGuestCustomerRequest($order);

            if ($this->trackingHelper->isEventMessageQueueAvailable(self::EVENT, $storeId)) {
                $this->dataItemPublisher->publish(OrderSender::MODEL, $order->getId(), $order->getStoreId());
                if ($guestCustomerRequest) {
                    $this->eventPublisher->publish(self::CUSTOMER_UPDATE, $guestCustomerRequest, $storeId);
                }
            } else {
                $this->orderSender->sendItems([$order], $storeId);
                if ($guestCustomerRequest) {
                    $this->customerSender->batchAddOrUpdateClients([$guestCustomerRequest], $storeId);
                }
            }
        } catch (\Exception $e) {
            if (!$e instanceof ApiException) {
                $this->loggerHelper->getLogger()->error($e);
            }
        }
    }

    /**
     * Prepare guest customer request
     *
     * @param Order $order
     * @return CreateaClientinCRMRequest|null
     */
    protected function prepareGuestCustomerRequest(Order $order): ?CreateaClientinCRMRequest
    {
        if (!$order->getCustomerIsGuest()) {
            return null;
        }

        $shippingAddress = $order->getShippingAddress();

        $phone = null;
        if ($shippingAddress) {
            $phone = $shippingAddress->getTelephone();
        }

        return new CreateaClientinCRMRequest([
            'email' => $order->getCustomerEmail(),
            'uuid' => $this->trackingHelper->getClientUuid(),
            'phone' => $phone,
            'first_name' => $order->getCustomerFirstname(),
            'last_name' => $order->getCustomerLastname()
        ]);
    }
}
