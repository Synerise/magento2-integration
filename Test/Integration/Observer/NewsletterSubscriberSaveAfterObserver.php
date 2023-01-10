<?php

namespace Synerise\Integration\Test\Integration\Observer;


use Magento\Newsletter\Model\Subscriber;
use Magento\TestFramework\Helper\Bootstrap;
use Synerise\Integration\Helper\Identity;
use Synerise\Integration\Helper\Update\ClientAgreement;
use Synerise\Integration\Observer\NewsletterSubscriberSaveAfter;

class NewsletterSubscriberSaveAfterObserver extends \PHPUnit\Framework\TestCase
{
    const FIXTURE_EMAIL = 'customer@example.com';

    /**
     * @var \Magento\Framework\Event\Config $frameworkEvent
     */
    private $eventConfig;

    /**
     * @var ClientAgreement
     */
    private $clientAgreementHelper
    ;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->eventConfig = $this->objectManager->create(\Magento\Framework\Event\Config::class);

        $this->clientAgreementHelper = $this->objectManager->get(ClientAgreement::class);
    }

    public function testObserverRegistration()
    {
        $observers = $this->eventConfig->getObservers('newsletter_subscriber_save_after');

        $this->assertArrayHasKey('synerise_newsletter_subscriber_save_after', $observers);
        $expectedClass = NewsletterSubscriberSaveAfter::class;
        $this->assertSame($expectedClass, $observers['synerise_newsletter_subscriber_save_after']['instance']);
    }

    public function testNewsletterSubscribeSaveAfter()
    {
        /** @var Subscriber $subscriber */
        $subscriber = $this->objectManager->create(Subscriber::class);
        $subscriber->setStoreId(1)
            ->setSubscriberEmail(self::FIXTURE_EMAIL)
            ->setSubscriberStatus(Subscriber::STATUS_SUBSCRIBED);

        $request = $this->clientAgreementHelper->prepareCreateClientRequest($subscriber);

        $this->assertTrue($request->valid());
        $this->assertEquals(self::FIXTURE_EMAIL, $request->getEmail());
        $this->assertEquals(Identity::generateUuidByEmail(self::FIXTURE_EMAIL), $request->getUuid());

        $agreements = $request->getAgreements();
        $this->assertEquals(1, $agreements['email']);
    }
}