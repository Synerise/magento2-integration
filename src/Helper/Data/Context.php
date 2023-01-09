<?php

namespace Synerise\Integration\Helper\Data;

use DateTime;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Mobile_Detect;

class Context
{
    const APPLICATION_NAME = 'magento2';

    const FORMAT_ISO_8601 = 'Y-m-d\TH:i:s.v\Z';

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
    }

    /**
     * @param DateTime $dateTime
     * @return string
     */
    public function formatDateTimeAsIso8601(DateTime $dateTime): string
    {
        return $dateTime->format(self::FORMAT_ISO_8601);
    }

    /**
     * @return string
     */
    public function getApplicationName(): string
    {
        return self::APPLICATION_NAME;
    }

    /**
     * @return string
     */
    public function getCurrentTime(): string
    {
        return (new DateTime())->format(self::FORMAT_ISO_8601);
    }

    /**
     * @return string
     */
    public function getSource(): string
    {
        $mobileDetect = new Mobile_Detect();
        return $mobileDetect->isMobile() ? "WEB_MOBILE" : "WEB_DESKTOP";
    }

    /**
     * @param $storeId
     * @return string|null
     */
    public function getStoreBaseUrl($storeId = null): ?string
    {
        try {
            $store = $this->storeManager->getStore($storeId);
            return $store ? $store->getBaseUrl() : null;
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * @return int|null
     */
    public function getStoreId(): ?int
    {
        try {
            return $this->storeManager->getStore()->getId();
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }
}