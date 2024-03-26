<?php

namespace Synerise\Integration\Helper\Tracking;

use DateTime;
use Exception;
use InvalidArgumentException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Mobile_Detect;
use Ramsey\Uuid\Uuid;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Model\Config\Source\EventTracking\Events;

class Context
{
    protected const FORMAT_ISO_8601 = 'Y-m-d\TH:i:s.v\Z';

    protected const APPLICATION_NAME = 'magento2';

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Logger
     */
    protected $loggerHelper;

    /**
     * @param StoreManagerInterface $storeManager
     * @param Logger $loggerHelper
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Logger $loggerHelper
    ) {
        $this->storeManager = $storeManager;
        $this->loggerHelper = $loggerHelper;
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
            'source' => $this->getSource(),
            'applicationName' => $this->getApplicationName(),
            'storeId' => $this->getStoreId(),
            'storeUrl' => $this->getStoreBaseUrl()
        ];
    }

    /**
     * Get application name
     *
     * @return string
     */
    public function getApplicationName(): string
    {
        return self::APPLICATION_NAME;
    }

    /**
     * Get current datetime in ISO8601 format
     *
     * @return string
     */
    public function getCurrentTime(): string
    {
        return $this->formatDateTimeAsIso8601(new DateTime());
    }

    /**
     * Format datetime as ISO8601
     *
     * @param DateTime $dateTime
     * @return string
     */
    public function formatDateTimeAsIso8601(DateTime $dateTime): string
    {
        return $dateTime->format(self::FORMAT_ISO_8601);
    }

    /**
     * Get source: web or mobile
     *
     * @return string
     */
    public function getSource(): string
    {
        $mobileDetect = new Mobile_Detect();
        return $mobileDetect->isMobile() ? "WEB_MOBILE" : "WEB_DESKTOP";
    }

    /**
     * Get store base url
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getStoreBaseUrl(?int $storeId = null): ?string
    {
        try {
            $store = $this->storeManager->getStore($storeId);
            return $store ? $store->getBaseUrl() : null;
        } catch (NoSuchEntityException $e) {
            $this->loggerHelper->warning($e);
            return null;
        }
    }

    /**
     * Get store ID
     *
     * @return int|null
     */
    public function getStoreId(): ?int
    {
        try {
            $store = $this->storeManager->getStore();
            return $store ? $store->getId() : null;
        } catch (NoSuchEntityException $e) {
            $this->loggerHelper->warning($e);
            return null;
        }
    }

    /**
     * Get currency code of current store
     *
     * @param int|null $storeId
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getCurrencyCode(?int $storeId = null): string
    {
        return $this->storeManager->getStore($storeId)->getCurrentCurrency()->getCode();
    }
}
