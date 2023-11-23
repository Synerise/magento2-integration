<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\EventClientAction;
use Synerise\Integration\Helper\Api;
use Synerise\Integration\Helper\Customer;
use Synerise\Integration\Helper\Event;
use Synerise\Integration\Helper\Queue;
use Synerise\Integration\Helper\Tracking;

class CustomerLogin implements ObserverInterface
{
    const EVENT = 'customer_login';

    /**
     * @var Api
     */
    protected $apiHelper;

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
        Api $apiHelper,
        Tracking $trackingHelper,
        Queue $queueHelper,
        Event $eventHelper
    ) {
        $this->apiHelper = $apiHelper;
        $this->trackingHelper = $trackingHelper;
        $this->queueHelper = $queueHelper;
        $this->eventHelper = $eventHelper;
    }

    public function execute(Observer $observer)
    {
        if (!$this->trackingHelper->isLiveEventTrackingEnabled(self::EVENT)) {
            return;
        }

        if ($this->trackingHelper->isAdminStore()) {
            return;
        }

        try {
            /** @var \Magento\Customer\Model\Customer $customer */
            $customer = $observer->getEvent()->getCustomer();
            $storeId = $customer->getStoreId();

            $this->trackingHelper->manageClientUuid($customer->getEmail());

            $eventClientAction = new EventClientAction([
                'event_salt' => $this->trackingHelper->generateEventSalt(),
                'time' => $this->trackingHelper->getCurrentTime(),
                'label' => $this->trackingHelper->getEventLabel(self::EVENT),
                'client' => $this->trackingHelper->prepareClientDataFromCustomer(
                    $customer,
                    $this->trackingHelper->generateUuidByEmail($customer->getEmail())
                ),
                'params' => [
                    'source' => $this->trackingHelper->getSource(),
                    'applicationName' => $this->trackingHelper->getApplicationName(),
                    'storeId' => $this->trackingHelper->getStoreId(),
                    'storeUrl' => $this->trackingHelper->getStoreBaseUrl()
                ]
            ]);

            if ($this->queueHelper->isQueueAvailable(self::EVENT, $storeId)) {
                $this->queueHelper->publishEvent(self::EVENT, $eventClientAction, $storeId);
            } else {
                $this->eventHelper->sendEvent(self::EVENT, $eventClientAction, $storeId);
            }
        } catch (ApiException $e) {
        } catch (\Exception $e) {
            $this->trackingHelper->getLogger()->error($e);
        }
    }
}
