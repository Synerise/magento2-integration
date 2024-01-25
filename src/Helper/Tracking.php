<?php

namespace Synerise\Integration\Helper;

use Exception;
use InvalidArgumentException;
use Magento\Quote\Model\Quote;
use Ramsey\Uuid\Uuid;
use Synerise\ApiClient\Model\Client;
use Synerise\Integration\Helper\Tracking\Context;
use Synerise\Integration\Helper\Tracking\Cookie;
use Synerise\Integration\Helper\Tracking\UuidGenerator;
use Synerise\Integration\Model\Config\Source\EventTracking\Events;
use Synerise\Integration\Model\Tracking\Config;
use Synerise\Integration\Model\Tracking\ConfigFactory;

class Tracking
{
    /**
     * @var Config[]
     */
    private $config = [];

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
     * @var ConfigFactory
     */
    private $configFactory;

    /**
     * @param ConfigFactory $configFactory
     * @param Cookie $cookieHelper
     * @param Context $contextHelper
     * @param UuidGenerator $uuidGenerator
     */
    public function __construct(
        ConfigFactory $configFactory,
        Cookie $cookieHelper,
        Context $contextHelper,
        UuidGenerator $uuidGenerator
    ) {
        $this->configFactory = $configFactory;
        $this->cookieHelper = $cookieHelper;
        $this->contextHelper = $contextHelper;
        $this->uuidGenerator = $uuidGenerator;
    }

    /**
     * Get tracking config by store ID
     *
     * @param int $storeId
     * @return Config
     */
    public function getConfig(int $storeId)
    {
        if (!isset($this->config[$storeId])) {
            $this->config[$storeId] = $this->configFactory->create($storeId);
        }

        return $this->config[$storeId];
    }

    /**
     * Check if event tracking is enabled & event should be tracked
     *
     * @param string $event
     * @param int $storeId
     * @return bool
     */
    public function isEventTrackingAvailable(string $event, int $storeId): bool
    {
        return $this->getConfig($storeId)->isEventTrackingEnabled($event);
    }

    /**
     * Check if event message queue is enabled & event should be sent via Message Queue
     *
     * @param string $event
     * @param int $storeId
     * @return bool
     */
    public function isEventMessageQueueAvailable(string $event, int $storeId): bool
    {
        return $this->getConfig($storeId)->isEventMessageQueueEnabled($event);
    }

    /**
     * Check if event message queue is enabled
     *
     * @param int $storeId
     * @return bool
     */
    public function isEventMessageQueueEnabled(int $storeId): bool
    {
        return $this->getConfig($storeId)->isEventMessageQueueEnabled();
    }

    /**
     * Get client uuid from cookie if not in admin scope
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
