<?php

namespace Synerise\Integration\Helper\Tracking;

use DateTime;
use Magento\Backend\Model\Auth\Session as BackedSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\ScopeResolverInterface;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Mobile_Detect;
use Synerise\Integration\Helper\Logger;

class Context
{
    protected const FORMAT_ISO_8601 = 'Y-m-d\TH:i:s.v\Z';

    protected const APPLICATION_NAME = 'magento2';

    /**
     * @var State
     */
    protected $state;

    /**
     * @var ScopeResolverInterface
     */
    protected $scopeResolver;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var BackedSession
     */
    protected $backendSession;

    /**
     * @var Logger
     */
    protected $loggerHelper;

    /**
     * @param State $state
     * @param BackedSession $backendSession
     * @param ScopeResolverInterface $scopeResolver
     * @param StoreManagerInterface $storeManager
     * @param CustomerSession $customerSession
     * @param Logger $loggerHelper
     */
    public function __construct(
        State $state,
        BackedSession $backendSession,
        ScopeResolverInterface $scopeResolver,
        StoreManagerInterface $storeManager,
        CustomerSession $customerSession,
        Logger $loggerHelper
    ) {
        $this->state = $state;
        $this->backendSession = $backendSession;
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
        $this->scopeResolver = $scopeResolver;
        $this->loggerHelper = $loggerHelper;
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
            $this->loggerHelper->getLogger()->warning($e);
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
            $this->loggerHelper->getLogger()->warning($e);
            return null;
        }
    }

    /**
     * Check if current area is frontend
     *
     * @return bool
     */
    public function isFrontend(): bool
    {
        try {
            return $this->state->getAreaCode() == 'frontend';
        } catch (LocalizedException $e) {
            return false;
        }
    }

    /**
     * Check is request use default scope
     *
     * @return bool
     */
    public function isAdminStore(): bool
    {
        return $this->isAdminLoggedIn() || $this->scopeResolver->getScope()->getCode() == Store::ADMIN_CODE;
    }

    /**
     * Check if user is logged in
     *
     * @return bool
     */
    public function isAdminLoggedIn(): bool
    {
        return $this->backendSession->getUser() && $this->backendSession->getUser()->getId();
    }

    /**
     * Check if customer is logged in
     *
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        return $this->customerSession->isLoggedIn();
    }
}
