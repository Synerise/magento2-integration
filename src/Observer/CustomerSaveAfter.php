<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;
use Synerise\Integration\Helper\Api;
use Synerise\Integration\Helper\Customer;
use Synerise\Integration\Helper\Event;
use Synerise\Integration\Helper\Queue;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\Model\ResourceModel\Cron\Queue as QueueResourceModel;

class CustomerSaveAfter implements ObserverInterface
{
    const EVENT = 'customer_save_after';

    const EXCLUDED_PATHS = [
        '/newsletter/manage/save/'
    ];

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
     * @var QueueResourceModel
     */
    protected $queueResourceModel;

    /**
     * @var Http
     */
    private $request;

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
        Customer $customerHelper,
        QueueResourceModel $queueResourceModel,
        Http $request,
        Queue $queueHelper,
        Event $eventHelper
    ) {
        $this->apiHelper = $apiHelper;
        $this->trackingHelper = $trackingHelper;
        $this->customerHelper = $customerHelper;
        $this->queueResourceModel = $queueResourceModel;
        $this->request = $request;
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

        try {
            $customer = $observer->getCustomer();
            $storeId = $customer->getStoreId();
            $customerParams = $this->customerHelper->preapreAdditionalParams($customer);
            $createClientInCRMRequest = new CreateaClientinCRMRequest($customerParams);

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

    protected function addItemToQueue(\Magento\Customer\Model\Customer $customer)
    {
        try {
            $this->queueResourceModel->addItem(
                'customer',
                $customer->getStoreId(),
                $customer->getId()
            );
        } catch (LocalizedException $e) {
            $this->trackingHelper->getLogger()->error($e);
        }
    }
}
