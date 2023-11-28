<?php

namespace Synerise\Integration\Observer;

use Magento\Customer\Model\Customer;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;
use Synerise\Integration\Helper\Event;
use Synerise\Integration\Helper\Queue;
use Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\Model\Synchronization\MessageQueue\Data\Single\Publisher;
use Synerise\Integration\Model\Synchronization\Sender\Customer as Sender;

class CustomerSaveAfter implements ObserverInterface
{
    const EVENT = 'customer_save_after';

    const EXCLUDED_PATHS = [
        '/newsletter/manage/save/'
    ];

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
     * @var Http
     */
    private $request;

    /**
     * @var Publisher
     */
    protected $queueHelper;

    /**
     * @var Event
     */
    protected $eventHelper;

    /**
     * @var Publisher
     */
    protected $publisher;

    public function __construct(
        Synchronization $synchronizationHelper,
        Tracking $trackingHelper,
        Sender $sender,
        Http $request,
        Publisher $publisher,
        Queue $queueHelper,
        Event $eventHelper
    ) {
        $this->synchronizationHelper = $synchronizationHelper;
        $this->trackingHelper = $trackingHelper;
        $this->sender = $sender;
        $this->request = $request;
        $this->publisher = $publisher;
        $this->queueHelper = $queueHelper;
        $this->eventHelper = $eventHelper;
    }

    public function execute(Observer $observer)
    {
        if (!$this->trackingHelper->isLiveEventTrackingEnabled(self::EVENT)) {
            return;
        }

        if (in_array($this->request->getPathInfo(), self::EXCLUDED_PATHS)) {
            return;
        }

        if (!$this->synchronizationHelper->isEnabledModel(Sender::MODEL)) {
            return;
        }

        try {
            $customer = $observer->getCustomer();
            $storeId = $customer->getStoreId();
            $createClientInCRMRequest = new CreateaClientinCRMRequest($this->sender->preapreParams($customer));

            if ($this->queueHelper->isQueueAvailable(self::EVENT, $storeId)) {
                $this->queueHelper->publishEvent('ADD_OR_UPDATE_CLIENT', $createClientInCRMRequest, $storeId);
            } else {
                $this->eventHelper->sendEvent('ADD_OR_UPDATE_CLIENT', $createClientInCRMRequest, $storeId);
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
        $this->publisher->publish(
            Sender::MODEL,
            (int) $customer->getId(),
            $customer->getStoreId()
        );
    }
}
