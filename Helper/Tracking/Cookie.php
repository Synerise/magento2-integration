<?php

namespace Synerise\Integration\Helper\Tracking;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException;
use Magento\Framework\Stdlib\Cookie\FailureToSendException;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Cookie
{
    public const COOKIE_SNRS_P = '_snrs_p';

    public const COOKIE_SNRS_PARAMS = '_snrs_params';

    public const COOKIE_SNRS_UUID = '_snrs_uuid';

    public const COOKIE_SNRS_RESET_UUID = '_snrs_reset_uuid';

    public const XML_PATH_PAGE_TRACKING_DOMAIN = 'synerise/page_tracking/domain';

    public const XML_PATH_EVENT_TRACKING_INCLUDE_PARAMS = 'synerise/event_tracking/include_params';

    /**
     * @var string|null
     */
    private $cookieDomain;

    /**
     * @var array
     */
    private $data;

    /**
     * @var CookieManagerInterface
     */
    private $cookieManager;

    /**
     * @var CookieMetadataFactory
     */
    private $cookieMetadataFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get client uuid from cookie
     *
     * @return string|null
     */
    public function getSnrsUuid(): ?string
    {
        if (!isset($this->data[self::COOKIE_SNRS_UUID])) {
            $this->data[self::COOKIE_SNRS_UUID] = $this->cookieManager->getCookie(self::COOKIE_SNRS_UUID);
        }
        return $this->data[self::COOKIE_SNRS_UUID];
    }

    /**
     * Set _snrs_reset_uuid cookie to reset uuid value via frontend tracker
     *
     * @param string $uuid
     * @return void
     * @throws CookieSizeLimitReachedException
     * @throws FailureToSendException
     * @throws InputException|NoSuchEntityException
     */
    public function setSnrsResetUuidCookie(string $uuid)
    {
        $cookieMeta = $this->cookieMetadataFactory
            ->createPublicCookieMetadata()
            ->setDurationOneYear()
            ->setDomain($this->getCookieDomain())
            ->setPath('/')
            ->setHttpOnly(false);

        $this->cookieManager->setPublicCookie(self::COOKIE_SNRS_RESET_UUID, $uuid, $cookieMeta);
    }

    /**
     * Get SNRS P cookie as string
     *
     * @return string|null
     */
    public function getSnrsPString(): ?string
    {
        return $this->cookieManager->getCookie(self::COOKIE_SNRS_P);
    }

    /**
     * Get SNRS P cookie as array or single value
     *
     * @param string|null $value
     * @return string|array|null
     */
    public function getSnrsP(?string $value = null)
    {
        if (!isset($this->data[self::COOKIE_SNRS_P])) {
            $paramsArray = [];
            $items = explode('&', (string) $this->getSnrsPString());
            if ($items) {
                foreach ($items as $item) {
                    $values = explode(':', $item);
                    if (isset($values[1])) {
                        $paramsArray[$values[0]] = $values[1];
                    }
                }
            }
            $this->data[self::COOKIE_SNRS_P] = $paramsArray;
        }

        if ($value) {
            return $this->data[self::COOKIE_SNRS_P][$value] ?? null;
        }

        return $this->data[self::COOKIE_SNRS_P];
    }

    /**
     * Set SNRS params as string
     *
     * @return string|null
     */
    public function getSnrsParamsString(): ?string
    {
        return $this->cookieManager->getCookie(self::COOKIE_SNRS_PARAMS);
    }

    /**
     * Get SNRS params cookie as array
     *
     * @param string|null $value
     * @return array|null
     */
    public function getSnrsParams(?string $value = null): ?array
    {
        if (!isset($this->data[self::COOKIE_SNRS_PARAMS])) {
            $paramsString = $this->getSnrsParamsString();
            $this->data[self::COOKIE_SNRS_PARAMS] = $paramsString ? json_decode($paramsString, true) : [];
        }

        if ($value) {
            return $this->data[self::COOKIE_SNRS_PARAMS][$value] ?? null;
        }

        return $this->data[self::COOKIE_SNRS_PARAMS];
    }

    /**
     * Set client uuid cookie
     *
     * @param string $uuid
     * @return void
     */
    public function setSnrsUuid(string $uuid)
    {
        $this->data[self::COOKIE_SNRS_UUID] = $uuid;
    }

    /**
     * Get cookie domain from config
     *
     * @return string|null
     * @throws NoSuchEntityException
     */
    public function getCookieDomain(): ?string
    {
        if (!$this->cookieDomain) {
            $this->cookieDomain = $this->scopeConfig->getValue(
                self::XML_PATH_PAGE_TRACKING_DOMAIN,
                ScopeInterface::SCOPE_STORE
            );

            if (!$this->cookieDomain) {
                // phpcs:ignore Magento2.Functions.DiscouragedFunction
                $parsedBasedUrl = parse_url($this->storeManager->getStore()->getBaseUrl());
                $this->cookieDomain = isset($parsedBasedUrl['host']) ? '.' . $parsedBasedUrl['host'] : null;
            }
        }

        return $this->cookieDomain;
    }

    /**
     * Include additional tracking params flag
     *
     * @param int|null $storeId
     * @return bool
     */
    public function shouldIncludeSnrsParams(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_EVENT_TRACKING_INCLUDE_PARAMS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
