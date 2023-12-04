<?php

namespace Synerise\Integration\Observer;

use Magento\Customer\Model\Customer;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;
use Synerise\Integration\MessageQueue\Sender\Event as EventSender;
use Synerise\Integration\MessageQueue\Publisher\Event as EventPublisher;
use Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\MessageQueue\Publisher\Data\Item as DataItemPublisher;
use Synerise\Integration\MessageQueue\Sender\Data\Customer as CustomerSender;

class CustomerSaveAfter implements ObserverInterface
{
    const EVENT = 'customer_save_after';

    const EXCLUDED_PATHS = [
        '/newsletter/manage/save/'
    ];

    /**
     * @var EventSender
     */
    protected $eventSender;

    /**
     * @var Synchronization
     */
    protected $synchronizationHelper;

    /**
     * @var Tracking
     */
    protected $trackingHelper;

    /**
     * @var Http
     */
    private $request;

    /**
     * @var EventPublisher
     */
    protected $eventPublisher;

    /**
     * @var CustomerSender
     */
    protected $customerSender;

    /**
     * @var DataItemPublisher
     */
    protected $dataItemPublisher;

    public function __construct(
        Synchronization $synchronizationHelper,
        Tracking $trackingHelper,
        EventSender $eventSender,
        Http $request,
        EventPublisher $eventPublisher,
        DataItemPublisher $dataItemPublisher,
        CustomerSender $customerSender
    ) {
        $this->synchronizationHelper = $synchronizationHelper;
        $this->trackingHelper = $trackingHelper;
        $this->eventSender = $eventSender;
        $this->request = $request;
        $this->eventPublisher = $eventPublisher;
        $this->dataItemPublisher = $dataItemPublisher;
        $this->customerSender = $customerSender;
    }

    public function execute(Observer $observer)
    {
        if (!$this->trackingHelper->isLiveEventTrackingEnabled(self::EVENT)) {
            return;
        }

        if (in_array($this->request->getPathInfo(), self::EXCLUDED_PATHS)) {
            return;
        }

        if (!$this->synchronizationHelper->isEnabledModel(CustomerSender::MODEL)) {
            return;
        }

        try {
            $customer = $observer->getCustomer();
            $storeId = $customer->getStoreId();
            $createClientInCRMRequest = new CreateaClientinCRMRequest($this->customerSender->preapreParams($customer));

            if ($this->trackingHelper->isQueueAvailable(self::EVENT, $storeId)) {
                $this->eventPublisher->publish('ADD_OR_UPDATE_CLIENT', $createClientInCRMRequest, $storeId);
            } else {
                $this->eventSender->send('ADD_OR_UPDATE_CLIENT', $createClientInCRMRequest, $storeId);
            }
        } catch (ApiException $e) {
            $this->addItemToQueue($observer->getCustomer());
        } catch (\Exception $e) {
            $this->trackingHelper->getLogger()->error($e);
            $this->addItemToQueue($observer->getCustomer());
        }
    }

    protected function addItemToQueue(Customer $customer)
    {
        $this->dataItemPublisher->publish(
            CustomerSender::MODEL,
            (int) $customer->getId(),
            $customer->getStoreId()
        );
    }
}
