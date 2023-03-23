<?php

namespace Synerise\Integration\Observer\Update;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\ValidatorException;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\Api\DefaultApi;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;
use Synerise\ApiClient\Model\CreateatransactionRequest;
use Synerise\ApiClient\Model\EventClientAction;
use Synerise\Integration\Helper\Api;
use Synerise\Integration\Helper\Api\Factory\DefaultApiFactory;
use Synerise\Integration\Helper\Api\Identity;
use Synerise\Integration\Helper\Api\Update\Transaction;
use Synerise\Integration\Helper\Event;
use Synerise\Integration\Helper\Queue;
use Synerise\Integration\Helper\Synchronization\Results;
use Synerise\Integration\Helper\Synchronization\Sender\Order as OrderSender;
use Synerise\Integration\Observer\AbstractObserver;

class OrderPlace  extends AbstractObserver implements ObserverInterface
{
    const EVENT = 'sales_order_place_after';

    /**
     * @var Event
     */
    protected $eventsHelper;

    /**
     * @var Queue
     */
    protected $queueHelper;

    /**
     * @var Identity
     */
    protected $identityHelper;

    /**
     * @var Transaction
     */
    protected $transactionHelper;

    /**
     * @var Results
     */
    protected $resultsHelper;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        Event $eventsHelper,
        Queue $queueHelper,
        Identity $identityHelper,
        Transaction $transactionHelper,
        Results $resultsHelper
    ) {
        $this->eventsHelper = $eventsHelper;
        $this->queueHelper = $queueHelper;
        $this->identityHelper = $identityHelper;
        $this->transactionHelper = $transactionHelper;
        $this->resultsHelper = $resultsHelper;

        parent::__construct($scopeConfig, $logger);
    }

    public function execute(Observer $observer)
    {
        if (!$this->isEventTrackingEnabled(self::EVENT)) {
            return;
        }

        try {
            /** @var \Magento\Sales\Model\Order $order */
            $order = $observer->getEvent()->getOrder();
            $storeId = $order->getStoreId();

            $uuid = $this->identityHelper->getClientUuid();
            if ($uuid && $this->identityHelper->manageClientUuid($uuid, $order->getCustomerEmail())) {
                $mergeRequest = $this->identityHelper->prepareMergeClientsRequest(
                    $order->getCustomerEmail(),
                    $uuid,
                    $this->identityHelper->getClientUuid()
                );

                $this->publishOrSendClientMerge($mergeRequest, $storeId);
            }

            $eventRequest = $this->transactionHelper->prepareCreateTransactionRequest(
                $order,
                $this->identityHelper->getClientUuid()
            );

            $this->publishOrSendEvent(self::EVENT, $eventRequest, $storeId);

            if (!$this->identityHelper->isAdminStore() && $order->getCustomerIsGuest()) {
                $updateRequest = $this->transactionHelper->prepareCreateClientRequest(
                    $order,
                    $this->identityHelper->getClientUuid()
                );

                $this->publishOrSendClientUpdate($updateRequest, $storeId);
            }

        } catch (\Exception $e) {
            $this->logger->error('Synerise Error', ['exception' => $e]);
        }
    }

    /**
     * @param string $eventName
     * @param CreateatransactionRequest $request
     * @param int $storeId
     * @return void
     * @throws ValidatorException
     */
    public function publishOrSendEvent(string $eventName, CreateatransactionRequest $request, int $storeId): void
    {
        try {
            if ($this->queueHelper->isQueueAvailable()) {
                $this->queueHelper->publishEvent($eventName, $request, $storeId);
            } else {
                $this->eventsHelper->sendEvent($eventName, $request, $storeId);
            }
        } catch (ApiException $e) {
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
}
