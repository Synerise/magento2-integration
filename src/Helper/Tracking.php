<?php

namespace Synerise\Integration\Helper;

use Exception;
use InvalidArgumentException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Synerise\ApiClient\Model\Client;
use Synerise\Integration\Helper\Tracking\Context;
use Synerise\Integration\Helper\Tracking\Cookie;
use Synerise\Integration\Helper\Tracking\UuidGenerator;
use Synerise\Integration\Model\Config\Source\EventTracking\Events;

class Tracking
{
    public const XML_PATH_EVENT_TRACKING_ENABLED = 'synerise/event_tracking/enabled';

    public const XML_PATH_EVENT_TRACKING_EVENTS = 'synerise/event_tracking/events';

    public const XML_PATH_QUEUE_ENABLED = 'synerise/queue/enabled';

    public const XML_PATH_QUEUE_EVENTS = 'synerise/queue/events';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Cookie
     */
    private $cookieHelper;

    /**
     * @var Context
     */
    private $contextHelper;

    /**
     * @var UuidGenerator
     */
    private $uuidGenerator;

    /**
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param Cookie $cookieHelper
     * @param Context $contextHelper
     * @param UuidGenerator $uuidGenerator
     */
    public function __construct(
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig,
        Cookie $cookieHelper,
        Context $contextHelper,
        UuidGenerator $uuidGenerator
    ) {
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->cookieHelper = $cookieHelper;
        $this->contextHelper = $contextHelper;
        $this->uuidGenerator = $uuidGenerator;
    }

    /**
     * Check if event should be tracked.
     *
     * @param string $event
     * @param int|null $storeId
     * @return bool
     */
    public function isEventTrackingAvailable(string $event, ?int $storeId = null): bool
    {
        if (!$this->scopeConfig->isSetFlag(
            self::XML_PATH_EVENT_TRACKING_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        )) {
            return false;
        }

        $events = explode(',', $this->scopeConfig->getValue(
            self::XML_PATH_EVENT_TRACKING_EVENTS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));

        return in_array($event, $events);
    }

    /**
     * Check if event should be sent via Message Queue
     *
     * @param string $event
     * @param int|null $storeId
     * @return bool
     */
    public function isEventMessageQueueAvailable(string $event, int $storeId = null): bool
    {
        if (!$this->isEventMessageQueueEnabled($storeId)) {
            return false;
        }

        return $this->isEventSelectedForMessageQueue($event, $storeId);
    }

    /**
     * Check if message queue is enabled for events
     *
     * @param int|null $storeId
     * @return bool
     */
    protected function isEventMessageQueueEnabled(int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_QUEUE_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if event is selected to be sent via Message Queue
     *
     * @param string $event
     * @param int|null $storeId
     * @return bool
     */
    protected function isEventSelectedForMessageQueue(string $event, ?int $storeId = null): bool
    {
        $events = explode(',', $this->scopeConfig->getValue(
            self::XML_PATH_QUEUE_EVENTS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));

        return in_array($event, $events);
    }

    /**
     * Get client uuid from cookie
     *
     * @return string|null
     */
    public function getClientUuid(): ?string
    {
        if ($this->getContext()->isAdminStore()) {
            return null;
        }

        return $this->cookieHelper->getSnrsUuid();
    }

    /**
     * Get event label
     *
     * @param string $event
     * @return string
     * @throws Exception
     */
    public function getEventLabel(string $event): string
    {
        if (!Events::OPTIONS[$event]) {
            throw new InvalidArgumentException('Invalid event');
        }

        return Events::OPTIONS[$event];
    }

    /**
     * Get context helper
     *
     * @return Context
     */
    public function getContext(): Context
    {
        return $this->contextHelper;
    }

    /**
     * Generate an uuid to be used as event salt
     *
     * @return string
     */
    public function generateEventSalt(): string
    {
        return (string) Uuid::uuid4();
    }

    /**
     * Get an array of context params
     *
     * @return array
     */
    public function prepareContextParams(): array
    {
        return [
            'source' => $this->contextHelper->getSource(),
            'applicationName' => $this->contextHelper->getApplicationName(),
            'storeId' => $this->contextHelper->getStoreId(),
            'storeUrl' => $this->contextHelper->getStoreBaseUrl()
        ];
    }

    /**
     * Prepare client data from customer object
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param null|string $uuid
     * @return array
     */
    public function prepareClientDataFromCustomer($customer, $uuid = null)
    {
        return [
            'email' => $customer->getEmail(),
            'customId' => $customer->getId(),
            'uuid' => $uuid
        ];
    }

    /**
     * Prepare client data from quote object
     *
     * @param Quote $quote
     * @return Client
     */
    public function prepareClientDataFromQuote($quote): Client
    {
        $data['uuid'] = $this->getClientUuid();

        if ($quote && $quote->getCustomerEmail()) {
            $data['email'] = $quote->getCustomerEmail();
            $data['uuid'] = $this->uuidGenerator->generateByEmail($data['email']);

            if ($quote->getCustomerId()) {
                $data['custom_id'] = $quote->getCustomerId();
            }
        }

        return new Client($data);
    }
}
