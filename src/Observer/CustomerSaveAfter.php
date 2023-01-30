<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Helper\Api;
use Synerise\Integration\Helper\Customer;
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
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Http
     */
    private $request;

    public function __construct(
        LoggerInterface $logger,
        Api $apiHelper,
        Tracking $trackingHelper,
        Customer $customerHelper,
        QueueResourceModel $queueResourceModel,
        Http $request
    ) {
        $this->logger = $logger;
        $this->apiHelper = $apiHelper;
        $this->trackingHelper = $trackingHelper;
        $this->customerHelper = $customerHelper;
        $this->queueResourceModel = $queueResourceModel;
        $this->request = $request;
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
            $this->customerHelper->addOrUpdateClient($observer->getCustomer());
        } catch (\Exception $e) {
            $this->logger->error('Synerise Api request failed', ['exception' => $e]);
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
            $this->logger->error('Adding order item to queue failed', ['exception' => $e]);
        }
    }
}
