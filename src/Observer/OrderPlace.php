<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;
use Synerise\ApiClient\Model\CreateatransactionRequest;
use Synerise\Integration\Helper\Api;
use Synerise\Integration\Helper\Event;
use Synerise\Integration\Helper\Order;
use Synerise\Integration\Helper\Queue;
use Synerise\Integration\Helper\Tracking;

class OrderPlace implements ObserverInterface
{
    const EVENT = 'sales_order_place_after';

    /**
     * @var Api
     */
    protected $apiHelper;

    /**
     * @var Order
     */
    protected $orderHelper;

    /**
     * @var Tracking
     */
    protected $trackingHelper;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Queue
     */
    protected $queueHelper;

    /**
     * @var Event
     */
    protected $eventHelper;

    public function __construct(
        LoggerInterface $logger,
        Api $apiHelper,
        Tracking $trackingHelper,
        Order $orderHelper,
        Queue $queueHelper,
        Event $eventHelper
    ) {
        $this->logger = $logger;
        $this->apiHelper = $apiHelper;
        $this->trackingHelper = $trackingHelper;
        $this->orderHelper = $orderHelper;
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

            $transactionRequest = new CreateatransactionRequest(
                $this->orderHelper->preapreOrderParams(
                    $order,
                    $this->trackingHelper->generateUuidByEmail($order->getCustomerEmail())
                )
            );

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
        } catch (\Exception $e) {
            $this->logger->error('Synerise Error', ['exception' => $e]);
        }
    }
}
