<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;
use Synerise\ApiClient\Model\CreateatransactionRequest;
use Synerise\Integration\Helper\Event;
use Synerise\Integration\Helper\Queue;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\Model\Synchronization\MessageQueue\Data\Single\Publisher;
use Synerise\Integration\Model\Synchronization\Sender\Order;

class OrderPlace implements ObserverInterface
{
    const EVENT = 'sales_order_place_after';

    /**
     * @var Publisher
     */
    protected $publisher;

    /**
     * @var Order
     */
    protected $sender;

    /**
     * @var Tracking
     */
    protected $trackingHelper;

    /**
     * @var Queue
     */
    protected $queueHelper;

    /**
     * @var Event
     */
    protected $eventHelper;

    public function __construct(
        Publisher $publisher,
        Tracking $trackingHelper,
        Order $sender,
        Queue $queueHelper,
        Event $eventHelper
    ) {
        $this->publisher = $publisher;
        $this->trackingHelper = $trackingHelper;
        $this->sender = $sender;
        $this->queueHelper = $queueHelper;
        $this->eventHelper = $eventHelper;
    }

    public function execute(Observer $observer)
    {
        if (!$this->trackingHelper->isEventTrackingEnabled(self::EVENT)) {
            return;
        }

        try {
            /** @var \Magento\Sales\Model\Order $order */
            $order = $observer->getEvent()->getOrder();
            $storeId = $order->getStoreId();

            $this->trackingHelper->manageClientUuid($order->getCustomerEmail());

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

            if ($this->queueHelper->isQueueAvailable(self::EVENT, $storeId)) {
                $this->queueHelper->publishEvent(self::EVENT, $transactionRequest, $storeId, $order->getId());
                if ($createAClientInCrmRequest) {
                    $this->queueHelper->publishEvent('ADD_OR_UPDATE_CLIENT', $createAClientInCrmRequest, $storeId);
                }
            } else {
                $this->eventHelper->sendEvent(self::EVENT, $transactionRequest, $storeId, $order->getId());
                if ($createAClientInCrmRequest) {
                    $this->eventHelper->sendEvent('ADD_OR_UPDATE_CLIENT', $createAClientInCrmRequest, $storeId);
                }
            }
        } catch (ApiException $e) {
            $this->addItemToQueue($order);
        } catch (\Exception $e) {
            $this->trackingHelper->getLogger()->error($e);
            $this->addItemToQueue($order);
        }
    }

    protected function addItemToQueue(\Magento\Sales\Model\Order $order)
    {
        $this->publisher->publish(
            'order',
            $order->getId(),
            $order->getStoreId()
        );
    }
}
