<?php

namespace Synerise\Integration\Observer\Update;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\ValidatorException;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\Api\DefaultApi;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;
use Synerise\Integration\Helper\Api;
use Synerise\Integration\Helper\Api\Factory\DefaultApiFactory;
use Synerise\Integration\Helper\Api\Identity;
use Synerise\Integration\Helper\Api\Update\Client;
use Synerise\Integration\Helper\Event;
use Synerise\Integration\Helper\MessageQueue;
use Synerise\Integration\Helper\Synchronization\Results;
use Synerise\Integration\Helper\Synchronization\Sender\Customer as CustomerSender;
use Synerise\Integration\Observer\AbstractObserver;

class CustomerSaveAfter  extends AbstractObserver implements ObserverInterface
{
    const EVENT = 'customer_save_after';

    const EXCLUDED_PATHS = [
        '/newsletter/manage/save/'
    ];

    protected $isSent = false;

    /**
     * @var Http
     */
    protected $request;

    /**
     * @var Client
     */
    protected $clientUpdate;

    /**
     * @var Event
     */
    protected $eventsHelper;

    /**
     * @var MessageQueue
     */
    protected $queueHelper;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        Http $request,
        Client $clientUpdate,
        Event $eventsHelper,
        MessageQueue $queueHelper
    ) {
        $this->request = $request;
        $this->clientUpdate = $clientUpdate;
        $this->eventsHelper = $eventsHelper;
        $this->queueHelper = $queueHelper;

        parent::__construct($scopeConfig, $logger);
    }

    public function execute(Observer $observer)
    {
        if (!$this->isEventTrackingEnabled(self::EVENT)) {
            return;
        }

        if ($this->isSent || in_array($this->request->getPathInfo(), self::EXCLUDED_PATHS)) {
            return;
        }

        try {
            $customer = $observer->getCustomer();

            $updateRequest = $this->clientUpdate->prepareCreateClientRequest(
                $observer->getCustomer(),
                Identity::generateUuidByEmail($customer->getEmail()),
                $customer->getStoreId()
            );

            $this->publishOrSendClientUpdate($updateRequest, $customer->getStoreId());
        } catch (\Exception $e) {
            $this->logger->error('Client update failed', ['exception' => $e]);
        }
    }

    /**
     * @param CreateaClientinCRMRequest $request
     * @param int $storeId
     * @return void
     * @throws ValidatorException
     */
    public function publishOrSendClientUpdate(CreateaClientinCRMRequest $request, int $storeId): void
    {
        try {
            if ($this->queueHelper->isQueueAvailable()) {
                $this->queueHelper->publishEvent(Event::ADD_OR_UPDATE_CLIENT, $request, $storeId);
            } else {
                $this->eventsHelper->sendEvent(Event::ADD_OR_UPDATE_CLIENT, $request, $storeId);
            }
            $this->isSent = true;
        } catch (ApiException $e) {
        }
    }
}
