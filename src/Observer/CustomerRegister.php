<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;
use Synerise\ApiClient\Model\EventClientAction;
use Synerise\Integration\Helper\Api;
use Synerise\Integration\Helper\Customer;
use Synerise\Integration\Helper\Event;
use Synerise\Integration\Helper\Queue;
use Synerise\Integration\Helper\Tracking;

class CustomerRegister implements ObserverInterface
{
    const EVENT = 'customer_register_success';

    /**
     * @var Api
     */
    protected $apiHelper;

    /**
     * @var Customer
     */
    protected $customerHelper;

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
        Customer $customerHelper,
        Queue $queueHelper,
        Event $eventHelper
    ) {
        $this->logger = $logger;
        $this->apiHelper = $apiHelper;
        $this->trackingHelper = $trackingHelper;
        $this->customerHelper = $customerHelper;
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
            /** @var \Magento\Customer\Model\Data\Customer $customer */
            $customer = $observer->getEvent()->getCustomer();
            $storeId = $customer->getStoreId();

            $this->trackingHelper->manageClientUuid($customer->getEmail());
            $emailUuid = $this->trackingHelper->generateUuidByEmail($customer->getEmail());

            $customerParams = $this->customerHelper->preapreAdditionalParams($customer);
            $customerParams['uuid'] = $emailUuid;
            $createClientInCRMRequest = new CreateaClientinCRMRequest($customerParams);

            $eventClientAction = new EventClientAction([
                'time' => $this->trackingHelper->getCurrentTime(),
                'label' => $this->trackingHelper->getEventLabel(self::EVENT),
                'client' => $this->customerHelper->prepareIdentityParams(
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
                $this->queueHelper->publishEvent('ADD_OR_UPDATE_CLIENT', $createClientInCRMRequest, $storeId, $customer->getId());
                $this->queueHelper->publishEvent(self::EVENT, $eventClientAction, $storeId);
            } else {
                $this->eventHelper->sendEvent('ADD_OR_UPDATE_CLIENT', $createClientInCRMRequest, $storeId, $customer->getId());
                $this->eventHelper->sendEvent(self::EVENT, $eventClientAction, $storeId);
            }
        } catch (ApiException $e) {
        } catch (\Exception $e) {
            $this->logger->error('Synerise Error', ['exception' => $e]);
        }
    }
}
