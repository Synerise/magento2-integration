<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;
use Synerise\ApiClient\Model\CreateatransactionRequest;
use Synerise\Integration\MessageQueue\Sender\Event;
use Synerise\Integration\MessageQueue\Publisher\Event as EventPublisher;
use Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\MessageQueue\Publisher\Data\Item as DataItemPublisher;
use Synerise\Integration\MessageQueue\Sender\Data\Order as Sender;

class OrderPlace implements ObserverInterface
{
    const EVENT = 'sales_order_place_after';

    /**
     * @var EventPublisher
     */
    protected $eventPublisher;

    /**
     * @var Sender
     */
    protected $sender;

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
     * @var Event
     */
    protected $event;

    public function __construct(
        EventPublisher $eventPublisher,
        Synchronization $synchronizationHelper,
        Tracking $trackingHelper,
        Sender $sender,
        DataItemPublisher $dataItemPublisher,
        Event $event
    ) {
        $this->eventPublisher = $eventPublisher;
        $this->synchronizationHelper = $synchronizationHelper;
        $this->trackingHelper = $trackingHelper;
        $this->sender = $sender;
        $this->dataItemPublisher = $dataItemPublisher;
        $this->event = $event;
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

            if (!$this->synchronizationHelper->isEnabledModel(Sender::MODEL)) {
                return;
            }

            $params = $this->sender->preapreOrderParams(
                $order,
                $this->trackingHelper->generateUuidByEmail($order->getCustomerEmail())
            );

            if(empty($params)) {
                return;
            }

            $transactionRequest = new CreateatransactionRequest($params);

            $createAClientInCrmRequest = null;
            if ($order->getCustomerIsGuest()) {
                $shippingAddress = $order->getShippingAddress();

                $phone = null;
                if ($shippingAddress) {
                    $phone = $shippingAddress->getTelephone();
                }

                $createAClientInCrmRequest = new CreateaClientinCRMRequest([
                    'email' => $order->getCustomerEmail(),
                    'uuid' => $this->trackingHelper->getClientUuid(),
                    'phone' => $phone,
                    'first_name' => $order->getCustomerFirstname(),
                    'last_name' => $order->getCustomerLastname()
                ]);
            }

            if ($this->trackingHelper->isQueueAvailable(self::EVENT, $storeId)) {
                $this->eventPublisher->publish(self::EVENT, $transactionRequest, $storeId, $order->getId());
                if ($createAClientInCrmRequest) {
                    $this->eventPublisher->publish('ADD_OR_UPDATE_CLIENT', $createAClientInCrmRequest, $storeId);
                }
            } else {
                $this->event->send(self::EVENT, $transactionRequest, $storeId, $order->getId());
                if ($createAClientInCrmRequest) {
                    $this->event->send('ADD_OR_UPDATE_CLIENT', $createAClientInCrmRequest, $storeId);
                }
            }
        } catch (ApiException $e) {
            $this->addItemToQueue($order);
        } catch (\Exception $e) {
            $this->trackingHelper->getLogger()->error($e);
            $this->addItemToQueue($order);
        }
    }

    protected function addItemToQueue(Order $order)
    {
        $this->dataItemPublisher->publish(
            Sender::MODEL,
            $order->getId(),
            $order->getStoreId()
        );
    }
}
