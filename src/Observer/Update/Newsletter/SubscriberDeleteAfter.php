<?php

namespace Synerise\Integration\Observer\Update\Newsletter;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\ValidatorException;
use Magento\Newsletter\Model\Subscriber;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;
use Synerise\Integration\Helper\Api\Update\ClientAgreement;
use Synerise\Integration\Helper\Event;
use Synerise\Integration\Helper\Queue;
use Synerise\Integration\Observer\AbstractObserver;

class SubscriberDeleteAfter extends AbstractObserver implements ObserverInterface
{
    const EVENT = 'newsletter_subscriber_save_after';

    /**
     * @var Event
     */
    protected $eventsHelper;

    /**
     * @var Queue
     */
    protected $queueHelper;

    /**
     * @var ClientAgreement
     */
    protected $clientAgreementHelper;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        Event $eventsHelper,
        Queue $queueHelper,
        ClientAgreement $clientAgreementHelper
    ) {
        $this->eventsHelper = $eventsHelper;
        $this->queueHelper = $queueHelper;
        $this->clientAgreementHelper = $clientAgreementHelper;

        parent::__construct($scopeConfig, $logger);
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->isEventTrackingEnabled(self::EVENT)) {
            return;
        }

        /** @var Subscriber $subscriber */
        $subscriber = $observer->getEvent()->getDataObject();

        try {
            $request = $this->clientAgreementHelper->prepareUnsubscribeRequest($subscriber);
            $this->publishOrSendClientUpdate($request, $subscriber->getStoreId());

        } catch (\Exception $e) {
            $this->logger->error('Failed to unsubscribe user', ['exception' => $e]);
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
