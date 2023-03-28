<?php

namespace Synerise\Integration\Observer\Update\Newsletter;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\ValidatorException;
use Magento\Newsletter\Model\Subscriber;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;
use Synerise\Integration\Helper\Api\Identity;
use Synerise\Integration\Helper\Api\Update\ClientAgreement;
use Synerise\Integration\Helper\Event;
use Synerise\Integration\Helper\MessageQueue;
use Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\Helper\Synchronization\Results;
use Synerise\Integration\Helper\Synchronization\Sender\Subscriber as SubscriberSender;
use Synerise\Integration\Observer\AbstractObserver;

class SubscriberSaveAfter  extends AbstractObserver implements ObserverInterface
{
    const EVENT = 'newsletter_subscriber_save_after';

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var Event
     */
    protected $eventsHelper;

    /**
     * @var MessageQueue
     */
    protected $queueHelper;

    /**
     * @var ClientAgreement
     */
    protected $clientAgreementHelper;

    /**
     * @var Identity
     */
    protected $identityHelper;

    /**
     * @var Results
     */
    protected $resultsHelper;

    /**
     * @var Synchronization
     */
    private $synchronizationHelper;

    public function __construct(
        CustomerSession      $customerSession,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface      $logger,
        Event                $eventsHelper,
        MessageQueue         $queueHelper,
        ClientAgreement      $clientAgreementHelper,
        Identity             $identityHelper,
        Results              $results,
        Synchronization      $synchronization
    ) {
        $this->customerSession = $customerSession;
        $this->eventsHelper = $eventsHelper;
        $this->queueHelper = $queueHelper;
        $this->clientAgreementHelper = $clientAgreementHelper;
        $this->identityHelper = $identityHelper;
        $this->resultsHelper = $results;
        $this->synchronizationHelper = $synchronization;

        parent::__construct($scopeConfig, $logger);
    }

    public function execute(Observer $observer)
    {
        if (!$this->isEventTrackingEnabled(self::EVENT)) {
            return;
        }

        /** @var Subscriber $subscriber */
        $subscriber = $observer->getEvent()->getDataObject();

        try {
            if (!$this->isCustomerLoggedIn()) {
                $uuid = $this->identityHelper->getClientUuid();
                if ($uuid && $this->identityHelper->manageClientUuid($uuid, $subscriber->getEmail())) {
                    $mergeRequest = $this->identityHelper->prepareMergeClientsRequest(
                        $subscriber->getEmail(),
                        $uuid,
                        $this->identityHelper->getClientUuid()
                    );

                    $this->publishOrSendClientMerge($mergeRequest, $subscriber->getStoreId());
                }
            }


            $updateRequest = $this->clientAgreementHelper->prepareSubscribeRequest($subscriber);
            $this->publishOrSendClientUpdate($updateRequest, $subscriber->getStoreId());

            $this->resultsHelper->markAsSent(SubscriberSender::MODEL, [$subscriber->getSubscriberId()]);

        } catch (\Exception $e) {
            $this->logger->error('Subscription update request failed', ['exception' => $e]);
            try {
                $this->addItemToQueue($subscriber);
            } catch(\Exception $e) {};
        }
    }


    /**
     * @param CreateaClientinCRMRequest[] $request
     * @param int $storeId
     * @return void
     * @throws ValidatorException
     */
    public function publishOrSendClientMerge(array $request, int $storeId): void
    {
        try {
            if ($this->queueHelper->isQueueAvailable()) {
                $this->queueHelper->publishEvent(Event::BATCH_ADD_OR_UPDATE_CLIENT, $request, $storeId);
            } else {
                $this->eventsHelper->sendEvent(Event::BATCH_ADD_OR_UPDATE_CLIENT, $request, $storeId);
            }
        } catch (ApiException $e) {
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
        } catch (ApiException $e) {
        }
    }

    /**
     * @param Subscriber $subscriber
     * @return void
     */
    protected function addItemToQueue(Subscriber $subscriber) {
        try {
            $this->synchronizationHelper->addItemToQueue(
                SubscriberSender::MODEL,
                $subscriber->getId(),
                $subscriber->getStoreId()
            );
        } catch(\Exception $e) {
            $this->logger->error('Adding Subscriber to queue failed', ['exception' => $e]);
        };
    }

    /**
     * Check if customer is logged in
     *
     * @return bool
     */
    protected function isCustomerLoggedIn(): bool
    {
        return $this->customerSession->isLoggedIn();
    }
}
