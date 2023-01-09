<?php

namespace Synerise\Integration\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Cookie extends \Magento\Framework\App\Helper\AbstractHelper
{
    const COOKIE_CLIENT_PARAMS = '_snrs_p';

    const COOKIE_CLIENT_UUID = '_snrs_uuid';

    const COOKIE_CLIENT_UUID_RESET = '_snrs_reset_uuid';

    const XML_PATH_PAGE_TRACKING_DOMAIN = 'synerise/page_tracking/domain';

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
     * @var string|null
     */
    private $cookieDomain = null;

    /**
     * @var array
     */
    private $cookieParams;

    public function __construct(
        Context $context,
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    public function getClientUuid()
    {
        return $this->cookieManager->getCookie(self::COOKIE_CLIENT_UUID);
    }

    public function getCookieDomain()
    {
        if (!$this->cookieDomain) {
            $this->cookieDomain = $this->scopeConfig->getValue(
                self::XML_PATH_PAGE_TRACKING_DOMAIN,
                ScopeInterface::SCOPE_STORE
            );

            if (!$this->cookieDomain) {
                $parsedBasedUrl = parse_url($this->storeManager->getStore()->getBaseUrl());
                $this->cookieDomain = isset($parsedBasedUrl['host']) ? '.'.$parsedBasedUrl['host'] : null;
            }
        }

        return $this->cookieDomain;
    }

    public function setClientUuidAndResetCookie($uuid)
    {
        $cookieMeta = $this->cookieMetadataFactory
            ->createPublicCookieMetadata()
            ->setDurationOneYear()
            ->setDomain($this->getCookieDomain())
            ->setPath('/')
            ->setHttpOnly(false);

        $this->cookieManager->setPublicCookie(self::COOKIE_CLIENT_UUID_RESET, $uuid, $cookieMeta);
        $this->clientUuid = $uuid;
    }

    public function getCookieParamsString()
    {
        return $this->cookieManager->getCookie(self::COOKIE_CLIENT_PARAMS);
    }

    public function getCookieParams($value = null)
    {
        if (!$this->cookieParams) {
            $paramsArray = [];
            $items = explode('&', $this->getCookieParamsString());
            if ($items) {
                foreach ($items as $item) {
                    $values = explode(':', $item);
                    if (isset($values[1])) {
                        $paramsArray[$values[0]] = $values[1];
                    }
                }
                $this->cookieParams = $paramsArray;
            }
        }

        if ($value) {
            return isset($this->cookieParams[$value]) ? $this->cookieParams[$value] : null;
        }

        return $this->cookieParams;
    }

}