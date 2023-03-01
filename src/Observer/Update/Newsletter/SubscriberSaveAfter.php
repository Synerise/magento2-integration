<?php

namespace Synerise\Integration\Observer\Update\Newsletter;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\ValidatorException;
use Magento\Newsletter\Model\Subscriber;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\Api\DefaultApi;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;
use Synerise\Integration\Helper\Api;
use Synerise\Integration\Helper\Api\Factory\DefaultApiFactory;
use Synerise\Integration\Helper\Api\Identity;
use Synerise\Integration\Helper\Api\Update\ClientAgreement;
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
     * @var Api
     */
    protected $apiHelper;

    /**
     * @var ClientAgreement
     */
    protected $clientAgreementHelper;

    /**
     * @var DefaultApiFactory
     */
    protected $defaultApiFactory;

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
        CustomerSession $customerSession,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        Api $apiHelper,
        ClientAgreement $clientAgreementHelper,
        DefaultApiFactory $defaultApiFactory,
        Identity $identityHelper,
        Results $results,
        Synchronization $synchronization
    ) {
        $this->customerSession = $customerSession;
        $this->apiHelper = $apiHelper;
        $this->clientAgreementHelper = $clientAgreementHelper;
        $this->defaultApiFactory = $defaultApiFactory;
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
                    $this->sendMergeClients(
                        $this->identityHelper->prepareMergeClientsRequest(
                            $subscriber->getEmail(),
                            $uuid,
                            $this->identityHelper->getClientUuid()
                        )
                    );
                }
            }

            $this->sendCreateClient(
                $this->clientAgreementHelper->prepareSubscribeRequest($subscriber),
                $subscriber->getStoreId()
            );

            $this->resultsHelper->markAsSent(SubscriberSender::MODEL, [$subscriber->getSubscriberId()]);

        } catch (\Exception $e) {
            $this->logger->error('Subscription update request failed', ['exception' => $e]);
            try {
                $this->addItemToQueue($subscriber);
            } catch(\Exception $e) {};
        }
    }

    /**
     * @param CreateaClientinCRMRequest $createAClientInCrmRequest
     * @param int|null $storeId
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     * @throws ApiException
     * @throws ValidatorException
     */
    public function sendCreateClient(CreateaClientinCRMRequest $createAClientInCrmRequest, int $storeId = null): array
    {
        return $this->getDefaultApiInstance($storeId)
            ->createAClientInCrmWithHttpInfo('4.4', $createAClientInCrmRequest);
    }

    /**
     * @param CreateaClientinCRMRequest[] $createAClientInCrmRequests
     * @param int|null $storeId
     * @return void
     */
    public function sendMergeClients(array $createAClientInCrmRequests, ?int $storeId = null): array {

        try {
            list ($body, $statusCode, $headers) = $this->getDefaultApiInstance($storeId)
                ->batchAddOrUpdateClientsWithHttpInfo(
                    'application/json',
                    '4.4',
                    $createAClientInCrmRequests
                );

            if ($statusCode == 202) {
                return [$body, $statusCode, $headers];
            } else {
                $this->logger->error('Client update with uuid reset failed');
            }
        } catch (\Exception $e) {
            $this->logger->error('Client update with uuid reset failed', ['exception' => $e]);
        }
        return [null, null, null];
    }

    /**
     * @param int|null $storeId
     * @return DefaultApi
     * @throws ValidatorException
     * @throws ApiException
     */
    public function getDefaultApiInstance(?int $storeId = null): DefaultApi
    {
        return $this->defaultApiFactory->get($this->apiHelper->getApiConfigByScope($storeId));
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
