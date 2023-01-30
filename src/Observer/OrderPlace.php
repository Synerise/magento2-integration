<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\Model\CreateatransactionRequest;
use Synerise\Integration\Helper\Api;
use Synerise\Integration\Helper\Order;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\Model\ResourceModel\Cron\Queue as QueueResourceModel;

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
     * @var QueueResourceModel
     */
    protected $queueResourceModel;

    public function __construct(
        LoggerInterface $logger,
        Api $apiHelper,
        Tracking $trackingHelper,
        Order $orderHelper,
        QueueResourceModel $queueResourceModel
    ) {
        $this->logger = $logger;
        $this->apiHelper = $apiHelper;
        $this->trackingHelper = $trackingHelper;
        $this->orderHelper = $orderHelper;
        $this->queueResourceModel = $queueResourceModel;
    }

    public function execute(Observer $observer)
    {
        if (!$this->trackingHelper->isEventTrackingEnabled(self::EVENT)) {
            return;
        }

        try {
            /** @var \Magento\Sales\Model\Order $order */
            $order = $observer->getEvent()->getOrder();

            $this->trackingHelper->manageClientUuid($order->getCustomerEmail());

            $createatransactionRequest = new CreateatransactionRequest(
                $this->orderHelper->preapreOrderParams(
                    $order,
                    $this->trackingHelper->generateUuidByEmail($order->getCustomerEmail())
                )
            );

            $this->apiHelper->getDefaultApiInstance($order->getStoreId())
                ->createATransaction('4.4', $createatransactionRequest);


            $this->orderHelper->markItemsAsSent([$order->getEntityId()]);

            if ($order->getCustomerIsGuest()) {
                $shippingAddress = $order->getShippingAddress();

                $phone = null;
                if($shippingAddress){
                    $phone = $shippingAddress->getTelephone();
                }

                $createAClientInCrmRequest = new \Synerise\ApiClient\Model\CreateaClientinCRMRequest(
                    [
                        'email' => $order->getCustomerEmail(),
                        'uuid' => $this->trackingHelper->getClientUuid(),
                        'phone' => $phone,
                        'first_name' => $order->getCustomerFirstname(),
                        'last_name' => $order->getCustomerLastname(),
                    ]
                );

                $this->apiHelper->getDefaultApiInstance()
                    ->createAClientInCrmWithHttpInfo('4.4', $createAClientInCrmRequest);
            }

        } catch (\Exception $e) {
            $this->addItemToQueue($order);

            $this->logger->error('Synerise Api request failed', ['exception' => $e]);
        }
    }

    protected function addItemToQueue(\Magento\Sales\Model\Order $order)
    {
        try {
            $this->queueResourceModel->addItem(
                'order',
                $order->getStoreId(),
                $order->getId()
            );
        } catch (LocalizedException $e) {
            $this->logger->error('Adding order item to queue failed', ['exception' => $e]);
        }
    }
}
