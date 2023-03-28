<?php

namespace Synerise\Integration\Helper\Api;

use DateTime;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mobile_Detect;

class Context
{
    const APPLICATION_NAME = 'magento2';

    const FORMAT_ISO_8601 = 'Y-m-d\TH:i:s.v\Z';

    protected $storeUrls = [];

    protected $storeToWebsite = [];

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
        if (!isset($this->storeUrls[$storeId])) {
            $store = $this->getStore($storeId);
            if ($store) {
                $storeId = $store->getId();
                $this->storeUrls[$storeId] = $store->getBaseUrl();
            }
        }

        return $this->storeUrls[$storeId] ?? null;
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

    /**
     * @param $storeId
     * @return StoreInterface|null
     */
    public function getStore($storeId = null): ?StoreInterface
    {
        try {
            $store = $this->storeManager->getStore($storeId);
            return $store ?: null;
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * @param int $storeId
     * @return int|null
     */
    public function getWebsiteIdByStoreId(int $storeId): ?int
    {
        if (!isset($this->storeToWebsite[$storeId])) {
            $store = $this->getStore($storeId);
            if ($store) {
                $storeId = $store->getId();
                $this->storeToWebsite[$storeId] = $store->getWebsiteId();
            }
        }

        return $this->storeToWebsite[$storeId] ?? null;
    }
}