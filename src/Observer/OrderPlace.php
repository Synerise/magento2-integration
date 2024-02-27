<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Helper\Tracking\Cookie;
use Synerise\Integration\Helper\Tracking\State;
use Synerise\Integration\Helper\Tracking\UuidManagement;
use Synerise\Integration\MessageQueue\Publisher\Data\Item as DataItemPublisher;
use Synerise\Integration\MessageQueue\Publisher\Event as EventPublisher;
use Synerise\Integration\Model\Synchronization\Config;
use Synerise\Integration\Model\Tracking\ConfigFactory;
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
     * @var ConfigFactory
     */
    protected $configFactory;

    /**
     * @var Cookie
     */
    protected $cookieHelper;

    /**
     * @var Logger
     */
    protected $loggerHelper;

    /**
     * @var State
     */
    protected $stateHelper;

    /**
     * @var UuidManagement
     */
    protected $uuidHelper;

    /**
     * @param CollectionFactory $collectionFactory
     * @param DataItemPublisher $dataItemPublisher
     * @param EventPublisher $eventPublisher
     * @param CustomerSender $customerSender
     * @param CustomerAdd $customerAdd
     * @param Cookie $cookieHelper
     * @param Logger $loggerHelper
     * @param State $stateHelper
     * @param ConfigFactory $configFactory
     * @param Config $synchronization
     * @param UuidManagement $uuidHelper
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        DataItemPublisher $dataItemPublisher,
        EventPublisher $eventPublisher,
        CustomerSender $customerSender,
        CustomerAdd $customerAdd,
        Cookie $cookieHelper,
        Logger $loggerHelper,
        State $stateHelper,
        ConfigFactory $configFactory,
        Config $synchronization,
        UuidManagement $uuidHelper
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->dataItemPublisher = $dataItemPublisher;
        $this->eventPublisher = $eventPublisher;
        $this->customerSender = $customerSender;
        $this->customerAdd = $customerAdd;
        $this->cookieHelper = $cookieHelper;
        $this->loggerHelper = $loggerHelper;
        $this->stateHelper = $stateHelper;
        $this->configFactory = $configFactory;
        $this->synchronization = $synchronization;
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

            $config = $this->configFactory->create($storeId);
            if (!$config->isEventTrackingEnabled(self::EVENT)) {
                return;
            }

            if ($this->stateHelper->isFrontend() && $order->getCustomerEmail()) {
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
                    $this->cookieHelper->getSnrsUuid()
                );

                if ($config->isEventMessageQueueEnabled(self::EVENT)) {
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
