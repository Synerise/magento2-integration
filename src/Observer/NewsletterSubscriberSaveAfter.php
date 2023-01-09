<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Newsletter\Model\Subscriber;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Helper\Identity;
use Synerise\Integration\Helper\Update\ClientAgreement;
use Synerise\Integration\Model\ResourceModel\Cron\Queue as QueueResourceModel;

class NewsletterSubscriberSaveAfter  extends AbstractObserver implements ObserverInterface
{
    const EVENT = 'newsletter_subscriber_save_after';

    /**
     * @var ClientAgreement
     */
    protected $clientAgreementHelper;

    /**
     * @var Identity
     */
    protected $identityHelper;

    /**
     * @var QueueResourceModel
     */
    protected $queueResourceModel;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        ClientAgreement $clientAgreementHelper,
        Identity $identityHelper,
        QueueResourceModel $queueResourceModel
    ) {
        $this->clientAgreementHelper = $clientAgreementHelper;
        $this->identityHelper = $identityHelper;
        $this->queueResourceModel = $queueResourceModel;

        parent::__construct($scopeConfig, $logger);
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if (!$this->isEventTrackingEnabled(self::EVENT)) {
            return;
        }

        $event = $observer->getEvent();
        /** @var Subscriber $subscriber */
        $subscriber = $event->getDataObject();

        try {
            if (!$this->identityHelper->isCustomerLoggedIn()) {
                $uuid = $this->identityHelper->getClientUuid();
                if ($this->identityHelper->manageClientUuid($uuid, $subscriber->getEmail())) {
                    $this->identityHelper->mergeClients(
                        $subscriber->getEmail(),
                        $uuid,
                        $this->identityHelper->getClientUuid()
                    );
                }
            }

            $this->identityHelper->sendCreateClient(
                $this->clientAgreementHelper->prepareCreateClientRequest($subscriber),
                $subscriber->getStoreId()
            );

            $this->clientAgreementHelper->markAsSent([
                $subscriber->getId()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Subscription update request failed', ['exception' => $e]);
            $this->addItemToQueue($subscriber);
        }
    }

    /**
     * @param $subscriber
     */
    protected function addItemToQueue($subscriber)
    {
        try {
            $this->queueResourceModel->addItem(
                'subscriber',
                $subscriber->getStoreId(),
                $subscriber->getId()
            );
        } catch (LocalizedException $e) {
            $this->logger->error('Adding subscription item to queue failed', ['exception' => $e]);
        }
    }
}
