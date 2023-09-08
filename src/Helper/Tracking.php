<?php

namespace Synerise\Integration\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\ScopeInterface;
use Mobile_Detect;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Synerise\ApiClient\Model\Client;
use Synerise\ApiClient\Model\CustomeventRequest;
use Synerise\Integration\Model\Config\Source\Debug\Exclude;

class Tracking
{
    const COOKIE_CLIENT_P = '_snrs_p';

    const COOKIE_CLIENT_PARAMS = '_snrs_params';

    const COOKIE_CLIENT_UUID = '_snrs_uuid';

    const COOKIE_CLIENT_UUID_RESET = '_snrs_reset_uuid';

    const FORMAT_ISO_8601 = 'Y-m-d\TH:i:s.v\Z';

    const APPLICATION_NAME = 'magento2';

    const XML_PATH_EVENT_TRACKING_ENABLED = 'synerise/event_tracking/enabled';

    const XML_PATH_EVENT_TRACKING_EVENTS = 'synerise/event_tracking/events';

    const XML_PATH_EVENT_TRACKING_INCLUDE_PARAMS = 'synerise/event_tracking/include_params';

    const XML_PATH_PAGE_TRACKING_ENABLED = 'synerise/page_tracking/enabled';

    const XML_PATH_PAGE_TRACKING_KEY = 'synerise/page_tracking/key';

    const XML_PATH_PAGE_TRACKING_DOMAIN = 'synerise/page_tracking/domain';

    const XML_PATH_PAGE_TRACKING_OPENGRAPH = 'synerise/page_tracking/opengraph';

    const XML_PATH_PAGE_TRACKING_CUSTOM_ENABLED = 'synerise/page_tracking/custom_enabled';

    const XML_PATH_PAGE_TRACKING_SCRIPT = 'synerise/page_tracking/script';

    /**
     * @var \Magento\Framework\Stdlib\CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * @var  CookieMetadataFactory
     */
    protected $cookieMetadataFactory;

    /**
     * @var \Magento\Framework\HTTP\Header
     */
    protected $httpHeader;

    /**
     * @var \Magento\Framework\App\ScopeResolverInterface
     */
    protected $scopeResolver;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Api
     */
    protected $apiHelper;

    protected $addressRepository;

    protected $customerSession;

    protected $subscriber;

    protected $clientUuid;

    protected $cookieP;

    protected $cookieParams;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $backendSession;

    /**
     * @var string|null
     */
    protected $cookieDomain;

    /**
     * @var string|null
     */
    protected $trackerKey;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig,
        \Magento\Backend\Model\Auth\Session $backendSession,
        \Magento\Framework\App\ScopeResolverInterface $scopeResolver,
        \Magento\Framework\HTTP\Header $httpHeader,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Newsletter\Model\Subscriber $subscriber,
        Api $apiHelper
    ) {
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->backendSession = $backendSession;
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->httpHeader = $httpHeader;
        $this->storeManager = $storeManager;
        $this->addressRepository = $addressRepository;
        $this->customerSession = $customerSession;
        $this->subscriber= $subscriber;
        $this->apiHelper = $apiHelper;
        $this->scopeResolver = $scopeResolver;
    }

    /**
     * Is the page tracking enabled.
     *
     * @return bool
     */
    public function isPageTrackingEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_PAGE_TRACKING_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Is the live event tracking enabled.
     *
     * @return bool
     */
    public function isLiveEventTrackingEnabled($event = null, $storeId = null)
    {
        if (!$this->apiHelper->isApiKeySet(ScopeInterface::SCOPE_STORE, $storeId)) {
            return false;
        }

        return $this->isEventTrackingEnabled($event, $storeId);
    }

    /**
     * Is the page tracking enabled.
     *
     * @return bool
     */
    public function isEventTrackingEnabled($event = null, $storeId = null)
    {
        if (!$this->scopeConfig->isSetFlag(
            self::XML_PATH_EVENT_TRACKING_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        )) {
            return false;
        }

        if (!$event) {
            return true;
        }

        $events = explode(',', $this->scopeConfig->getValue(
            self::XML_PATH_EVENT_TRACKING_EVENTS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));

        return in_array($event, $events);
    }

    /**
     * Include additional tracking params flag
     *
     * @return bool
     */
    public function shouldIncludeParams($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_EVENT_TRACKING_INCLUDE_PARAMS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function isLoggedIn()
    {
        return $this->customerSession->isLoggedIn();
    }

    public function isOpengraphEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_PAGE_TRACKING_OPENGRAPH
        );
    }

    /**
     * Tracking key
     *
     * @return string
     */
    public function getTrackerKey()
    {
        if (!isset($this->trackerKey)) {
            $this->trackerKey = $this->scopeConfig->getValue(
                self::XML_PATH_PAGE_TRACKING_KEY,
                ScopeInterface::SCOPE_STORE
            );
        }

        return $this->trackerKey;
    }

    public function isCustomScriptEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_PAGE_TRACKING_CUSTOM_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getCustomTrackingScript()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_PAGE_TRACKING_SCRIPT,
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getClientUuid()
    {
        if ($this->isAdminStore()) {
            return null;
        }

        if (!$this->clientUuid) {
            $this->clientUuid = $this->getClientUuidFromCookie();
        }
        return $this->clientUuid;
    }

    public function getClientUuidFromCookie()
    {
        return $this->cookieManager->getCookie(self::COOKIE_CLIENT_UUID);
    }

    public function getStoreBaseUrl($storeId = null)
    {
        $store = $this->storeManager->getStore($storeId);
        return $store ? $store->getBaseUrl() : null;
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
                $this->cookieDomain = isset($parsedBasedUrl['host']) ? '.' . $parsedBasedUrl['host'] : null;
            }
        }

        return $this->cookieDomain;
    }

    public function getStoreId()
    {
        return $this->storeManager->getStore()->getId();
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
        if (!$this->cookieParams && $this->getCookieParamsString()!= null) {
            $this->cookieParams = json_decode($this->getCookieParamsString());
        }

        if ($value) {
            return $this->cookieParams[$value] ?? null;
        }

        return $this->cookieParams;
    }

    public function getCookiePParamsString()
    {
        return $this->cookieManager->getCookie(self::COOKIE_CLIENT_P);
    }

    public function getCookiePParams($value = null)
    {
        if (!$this->cookieP && $this->getCookiePParamsString()!= null) {
            $paramsArray = [];
            $items = explode('&', $this->getCookiePParamsString());
            if ($items) {
                foreach ($items as $item) {
                    $values = explode(':', $item);
                    if (isset($values[1])) {
                        $paramsArray[$values[0]] = $values[1];
                    }
                }
                $this->cookieP = $paramsArray;
            }
        }

        if ($value) {
            return isset($this->cookieP[$value]) ? $this->cookieP[$value] : null;
        }

        return $this->cookieP;
    }

    public function getCurrentTime()
    {
        return (new \DateTime())->format(self::FORMAT_ISO_8601);
    }

    public function formatDateTimeAsIso8601(\DateTime $dateTime)
    {
        return $dateTime->format(self::FORMAT_ISO_8601);
    }

    public function getSource()
    {
        $mobileDetect = new Mobile_Detect();
        return $mobileDetect->isMobile() ? "WEB_MOBILE" : "WEB_DESKTOP";
    }

    public function getApplicationName()
    {
        return self::APPLICATION_NAME;
    }

    /**
     * @param string $event
     * @return string
     * @throws \Exception
     */
    public function getEventLabel($event)
    {
        if (!\Synerise\Integration\Model\Config\Source\EventTracking\Events::OPTIONS[$event]) {
            throw new \Exception('Invalid event');
        }

        return \Synerise\Integration\Model\Config\Source\EventTracking\Events::OPTIONS[$event];
    }

    /**
     * @param Quote $quote
     * @return Client
     */
    public function prepareClientDataFromQuote($quote): Client
    {
        $data['uuid'] = $this->getClientUuid();

        if ($quote && $quote->getCustomerEmail()) {
            $data['email'] = $quote->getCustomerEmail();
            $data['uuid'] = $this->generateUuidByEmail($data['email']);

            if ($quote->getCustomerId()) {
                $data['custom_id'] = $quote->getCustomerId();
            }
        }

        return new Client($data);
    }

    public function manageClientUuid($email)
    {
        if ($this->isAdminStore()) {
            return;
        }

        $uuid = $this->getClientUuidFromCookie();
        if (!$uuid) {
            return;
        }

        $emailUuid = $this->generateUuidByEmail($email);

        if ($uuid == $emailUuid) {
            // email uuid already set
            return;
        }

        // reset uuid via cookie
        $this->setClientUuidAndResetCookie((string) $emailUuid);

        $identityHash = $this->getCookiePParams('identityHash');
        if ($identityHash && $identityHash != $this->hashString($email)) {
            // Different user, skip merge.
            return;
        }

        $createAClientInCrmRequests = [
            new \Synerise\ApiClient\Model\CreateaClientinCRMRequest([
                'email' => $email,
                'uuid' => $emailUuid
            ]),
            new \Synerise\ApiClient\Model\CreateaClientinCRMRequest([
                'email' => $email,
                'uuid' => $uuid
            ])
        ];

        try {
            list($body, $statusCode, $headers) = $this->apiHelper->getDefaultApiInstance()
                ->batchAddOrUpdateClientsWithHttpInfo('application/json', '4.4', $createAClientInCrmRequests);

            if ($statusCode != 202 && !$this->isExcludedFromLogging(Exclude::EXCEPTION_CLIENT_MERGE_FAIL)) {
                $this->logger->error(
                    'Client update with uuid reset failed',
                    [
                        'api_response_body' => $body,
                        'api_response_code' => $statusCode
                    ]
                );
            }
        } catch (\Exception $e) {
            if (!$this->isExcludedFromLogging(Exclude::EXCEPTION_CLIENT_MERGE_FAIL)) {
                $this->logger->error($e);
            }
        }
    }

    public function hasItemDataChanges(Quote $quote)
    {
        return ($quote->dataHasChangedFor('subtotal') || $quote->dataHasChangedFor('items_qty'));
    }

    public function overflow32($v)
    {
        $v = $v % 4294967296;
        if ($v > 2147483647) {
            return $v - 4294967296;
        } elseif ($v < -2147483648) {
            return $v + 4294967296;
        } else {
            return $v;
        }
    }

    public function hashString($s)
    {
        $h = 0;
        $len = strlen($s);
        for ($i = 0; $i < $len; $i++) {
            $h = $this->overflow32(31 * $h + ord($s[$i]));
        }

        return $h;
    }

    public function generateUuidByEmail($email)
    {
        $namespace = 'ea1c3a9d-64a6-45d0-a70c-d2a055f350d3';
        return (string) Uuid::uuid5($namespace, $email);
    }

    /**
     * Check is request use default scope
     *
     * @return bool
     */
    public function isAdminStore()
    {
        return $this->isAdminLoggedIn() || $this->scopeResolver->getScope()->getCode() == \Magento\Store\Model\Store::ADMIN_CODE;
    }

    public function isAdminLoggedIn(): bool
    {
        return $this->backendSession->getUser() && $this->backendSession->getUser()->getId();
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function isExcludedFromLogging(string $exception): bool
    {
        $exclusions = explode(',', $this->scopeConfig->getValue(
            Exclude::XML_PATH_DEBUG_LOGGER_EXCLUDE,
        ));

        return in_array($exception, $exclusions);
    }
}
