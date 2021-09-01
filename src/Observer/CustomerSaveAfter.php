<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\Model\EventClientAction;
use Synerise\Integration\Helper\Api;
use Synerise\Integration\Helper\Customer;
use Synerise\Integration\Helper\Tracking;

class CustomerSaveAfter implements ObserverInterface
{
    const EVENT = 'customer_save_after';

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
     * @var Http
     */
    private $request;

    public function __construct(
        LoggerInterface $logger,
        Api $apiHelper,
        Tracking $trackingHelper,
        Customer $customerHelper,
        Http $request
    ) {
        $this->logger = $logger;
        $this->apiHelper = $apiHelper;
        $this->trackingHelper = $trackingHelper;
        $this->customerHelper = $customerHelper;
        $this->request = $request;
    }

    public function execute(Observer $observer)
    {
        if(!$this->trackingHelper->isEventTrackingEnabled(self::EVENT)) {
            return;
        }

        if($this->request->getPathInfo() == '/customer/account/createpost/') {
            return;
        }

        try {
            $this->customerHelper->addOrUpdateClient($observer->getCustomer());
        } catch (\Exception $e) {
            $this->logger->error('Synerise Api request failed', ['exception' => $e]);
        }
    }
}