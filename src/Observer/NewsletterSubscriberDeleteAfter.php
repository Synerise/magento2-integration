<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Newsletter\Model\Subscriber;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;
use Synerise\Integration\Helper\Identity;

class NewsletterSubscriberDeleteAfter  extends AbstractObserver implements ObserverInterface
{
    const EVENT = 'newsletter_subscriber_save_after';

    /**
     * @var Identity
     */
    private $identityHelper;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        Identity $clientAgreementHelper
    ) {
        $this->identityHelper = $clientAgreementHelper;

        parent::__construct($scopeConfig, $logger);
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->isEventTrackingEnabled(self::EVENT)) {
            return;
        }

        $event = $observer->getEvent();

        /** @var Subscriber $subscriber */
        $subscriber = $event->getDataObject();

        try {
            $this->identityHelper->sendCreateClient(
                new CreateaClientinCRMRequest([
                    'email' => $subscriber->getSubscriberEmail(),
                    'agreements' => ['email' =>  0]
                ]),
                $subscriber->getStoreId()
            );
        } catch (\Exception $e) {
            $this->logger->error('Failed to unsubscribe user', ['exception' => $e]);
        }
    }
}
