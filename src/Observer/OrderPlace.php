<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;
use Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\MessageQueue\Publisher\Data\Item as DataItemPublisher;
use Synerise\Integration\MessageQueue\Publisher\Event as EventPublisher;
use Synerise\Integration\MessageQueue\Sender\Data\Order as OrderSender;
use Synerise\Integration\MessageQueue\Sender\Event as EventSender;


class OrderPlace implements ObserverInterface
{
    const EVENT = 'sales_order_place_after';

    const CUSTOMER_UPDATE = 'customer_update_guest_order';

    /**
     * @var Synchronization
     */
    protected $synchronizationHelper;

    /**
     * @var Tracking
     */
    protected $trackingHelper;

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
     * @var EventSender
     */
    protected $eventSender;

    public function __construct(
        DataItemPublisher $dataItemPublisher,
        EventPublisher $eventPublisher,
        OrderSender $orderSender,
        EventSender $eventSender,
        Synchronization $synchronizationHelper,
        Tracking $trackingHelper
    ) {
        $this->dataItemPublisher = $dataItemPublisher;
        $this->eventPublisher = $eventPublisher;
        $this->orderSender = $orderSender;
        $this->eventSender = $eventSender;
        $this->synchronizationHelper = $synchronizationHelper;
        $this->trackingHelper = $trackingHelper;
    }

    public function execute(Observer $observer)
    {
        if (!$this->trackingHelper->isEventTrackingEnabled(self::EVENT)) {
            return;
        }

        try {
            /** @var Order $order */
            $order = $observer->getEvent()->getOrder();
            $storeId = $order->getStoreId();

            $this->trackingHelper->manageClientUuid($order->getCustomerEmail());

            if (!$this->synchronizationHelper->isEnabledModel(OrderSender::MODEL)) {
                return;
            }

            $guestCustomerRequest = $this->prepareGuestCustomerRequest($order);

            if ($this->trackingHelper->isQueueAvailable(self::EVENT, $storeId)) {
                $this->dataItemPublisher->publish(OrderSender::MODEL,$order->getId(), $order->getStoreId());
                if ($guestCustomerRequest) {
                    $this->eventPublisher->publish(self::CUSTOMER_UPDATE, $guestCustomerRequest, $storeId);
                }
            } else {
                $this->orderSender->sendItems([$order], $storeId);
                if ($guestCustomerRequest) {
                    $this->eventSender->send(self::CUSTOMER_UPDATE, $guestCustomerRequest, $storeId);
                }
            }
        } catch (ApiException $e) {
        } catch (\Exception $e) {
            $this->trackingHelper->getLogger()->error($e);
        }
    }

    /**
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
