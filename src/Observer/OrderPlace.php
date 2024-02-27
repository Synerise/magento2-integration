<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\Helper\Tracking\UuidManagement;
use Synerise\Integration\MessageQueue\Publisher\Data\Item as DataItemPublisher;
use Synerise\Integration\MessageQueue\Publisher\Event as EventPublisher;
use Synerise\Integration\Model\Synchronization\Config;
use Synerise\Integration\SyneriseApi\Mapper\CustomerAdd;
use Synerise\Integration\SyneriseApi\Sender\Data\Order as OrderSender;
use Synerise\Integration\SyneriseApi\Sender\Data\Customer as CustomerSender;

class OrderPlace implements ObserverInterface
{
    public const EVENT = 'sales_order_place_after';

    public const CUSTOMER_UPDATE = 'customer_update_guest_order';

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

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
     * @var CustomerAdd
     */
    protected $customerAdd;

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
     * @param CollectionFactory $collectionFactory
     * @param DataItemPublisher $dataItemPublisher
     * @param EventPublisher $eventPublisher
     * @param OrderSender $orderSender
     * @param CustomerSender $customerSender
     * @param CustomerAdd $customerAdd
     * @param Logger $loggerHelper
     * @param Config $synchronization
     * @param Tracking $trackingHelper
     * @param UuidManagement $uuidHelper
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        DataItemPublisher $dataItemPublisher,
        EventPublisher $eventPublisher,
        OrderSender $orderSender,
        CustomerSender $customerSender,
        CustomerAdd $customerAdd,
        Logger $loggerHelper,
        Config $synchronization,
        Tracking $trackingHelper,
        UuidManagement $uuidHelper
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->dataItemPublisher = $dataItemPublisher;
        $this->eventPublisher = $eventPublisher;
        $this->orderSender = $orderSender;
        $this->customerSender = $customerSender;
        $this->customerAdd = $customerAdd;
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
            if (!$storeId = $this->getOrderStoreId($order)) {
                return;
            }

            if (!$this->trackingHelper->isEventTrackingAvailable(self::EVENT, $storeId)) {
                return;
            }

            if ($this->trackingHelper->getContext()->isFrontend() && $order->getCustomerEmail()) {
                $this->uuidHelper->manageByEmail(
                    $order->getCustomerEmail(),
                    $storeId
                );
            }

            if ($this->synchronization->isModelEnabled(OrderSender::MODEL)) {
                $this->dataItemPublisher->publish(OrderSender::MODEL, $order->getId(), $storeId);
            }

            if ($order->getCustomerIsGuest() && $order->getCustomerEmail()) {
                $guestCustomerRequest = $this->customerAdd->prepareRequestFromOrder(
                    $order,
                    $this->trackingHelper->getClientUuid()
                );

                if ($this->trackingHelper->isEventMessageQueueEnabled($storeId)) {
                    $this->eventPublisher->publish(self::CUSTOMER_UPDATE, $guestCustomerRequest, $storeId);
                } else {
                    $this->customerSender->batchAddOrUpdateClients([$guestCustomerRequest], $storeId);
                }
            }
        } catch (\Exception $e) {
            if (!$e instanceof ApiException) {
                $this->loggerHelper->error($e);
            }
        }
    }

    /**
     * Get store ID from order
     *
     * @param Order $order
     * @return int|null
     */
    protected function getOrderStoreId(Order $order): ?int
    {
        if ($storeId = $order->getStoreId()) {
            return $storeId;
        }

        $storeIds = $this->collectionFactory->create()
            ->addFieldToFilter('entity_id', $order->getEntityId())
            ->addFieldToSelect('store_id')
            ->getColumnValues('store_id');

        return $storeIds[0] ?? null;
    }
}
