<?php

namespace Synerise\Integration\Observer\Event\Customer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\ValidatorException;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;
use Synerise\ApiClient\Model\EventClientAction;
use Synerise\Integration\Helper\Api\Identity;
use Synerise\Integration\Helper\Api\Event\Client;
use Synerise\Integration\Helper\Event;
use Synerise\Integration\Helper\MessageQueue;
use Synerise\Integration\Observer\AbstractObserver;

abstract class AbstractCustomerEvent extends AbstractObserver implements ObserverInterface
{
    /**
     * @var Event
     */
    protected $eventsHelper;

    /**
     * @var MessageQueue
     */
    protected $queueHelper;

    /**
     * @var Client
     */
    protected $clientHelper;

    /**
     * @var Identity
     */
    protected $identityHelper;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface      $logger,
        Event                $eventsHelper,
        MessageQueue         $queueHelper,
        Client               $clientHelper,
        Identity             $identityHelper
    ) {
        $this->eventsHelper = $eventsHelper;
        $this->queueHelper = $queueHelper;
        $this->clientHelper = $clientHelper;
        $this->identityHelper = $identityHelper;

        parent::__construct($scopeConfig, $logger);
    }

    abstract public function execute(Observer $observer);

    /**
     * @param string $eventName
     * @param EventClientAction $request
     * @param int $storeId
     * @return void
     * @throws ValidatorException
     */
    public function publishOrSendEvent(string $eventName, EventClientAction $request, int $storeId): void
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
}