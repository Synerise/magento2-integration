<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\EventClientAction;
use Synerise\Integration\Helper\Api;
use Synerise\Integration\MessageQueue\Sender\Event;
use Synerise\Integration\MessageQueue\Publisher\Event as Publisher;
use Synerise\Integration\Helper\Tracking;

class CustomerRegister implements ObserverInterface
{
    const EVENT = 'customer_register_success';

    /**
     * @var Api
     */
    protected $apiHelper;

    /**
     * @var Tracking
     */
    protected $trackingHelper;

    /**
     * @var Publisher
     */
    protected $publisher;

    /**
     * @var Event
     */
    protected $sender;

    public function __construct(
        Api $apiHelper,
        Tracking $trackingHelper,
        Publisher $publisher,
        Event $sender
    ) {
        $this->apiHelper = $apiHelper;
        $this->trackingHelper = $trackingHelper;
        $this->publisher = $publisher;
        $this->sender = $sender;
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
            /** @var \Magento\Customer\Model\Data\Customer $customer */
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

            if ($this->trackingHelper->isQueueAvailable(self::EVENT, $storeId)) {
                $this->publisher->publish(self::EVENT, $eventClientAction, $storeId);
            } else {
                $this->sender->send(self::EVENT, $eventClientAction, $storeId);
            }
        } catch (ApiException $e) {
        } catch (\Exception $e) {
            $this->trackingHelper->getLogger()->error($e);
        }
    }
}
