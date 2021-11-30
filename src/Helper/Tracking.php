<?php

namespace Synerise\Integration\Helper;

use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Ramsey\Uuid\Uuid;

class Tracking extends \Magento\Framework\App\Helper\AbstractHelper
{
    const COOKIE_CLIENT_PARAMS = '_snrs_p';

    const COOKIE_CLIENT_UUID = '_snrs_uuid';

    const COOKIE_CLIENT_UUID_RESET = '_snrs_reset_uuid';

    const FORMAT_ISO_8601 = 'Y-m-d\TH:i:s.v\Z';

    const APPLICATION_NAME = 'magento2';

    /**
     * @var \Magento\Framework\Stdlib\CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * @var  CookieMetadataFactory
     */
    protected $cookieMetadataFactory;

    /**
     * @var \Magento\Framework\App\ScopeResolverInterfaceScopeResolverInterface
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

    /**
     * @var \Magento\Catalog\Helper\Image
     */
    protected $imageHelper;

    protected $addressRepository;

    protected $customerSession;

    protected $subscriber;

    protected $clientUuid;

    protected $cookieParams;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\ScopeResolverInterface $scopeResolver,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        \Magento\Catalog\Helper\Image $imageHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Newsletter\Model\Subscriber $subscriber,
        Api $apiHelper
    ) {
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->storeManager = $storeManager;
        $this->imageHelper = $imageHelper;
        $this->addressRepository = $addressRepository;
        $this->customerSession = $customerSession;
        $this->subscriber= $subscriber;
        $this->apiHelper = $apiHelper;
        $this->scopeResolver = $scopeResolver;
        parent::__construct($context);
    }

    /**
     * Is the page tracking enabled.
     *
     * @return bool
     */
    public function isPageTrackingEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            \Synerise\Integration\Helper\Config::XML_PATH_PAGE_TRACKING_ENABLED
        );
    }

    /**
     * Is the page tracking enabled.
     *
     * @return bool
     */
    public function isEventTrackingEnabled($event = null)
    {
        if (!$this->scopeConfig->isSetFlag(
            \Synerise\Integration\Helper\Config::XML_PATH_EVENT_TRACKING_ENABLED
        )) {
            return false;
        }

        if (!$event) {
            return true;
        }

        $events = explode(',', $this->scopeConfig->getValue(
            \Synerise\Integration\Helper\Config::XML_PATH_EVENT_TRACKING_EVENTS
        ));

        return in_array($event, $events);
    }

    public function isLoggedIn()
    {
        return $this->customerSession->isLoggedIn();
    }

    public function isOpengraphEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            \Synerise\Integration\Helper\Config::XML_PATH_PAGE_TRACKING_OPENGRAPH
        );
    }

    /**
     * Tracking key
     *
     * @return string
     */
    public function getTrackingKey()
    {
        return $this->scopeConfig->getValue(
            \Synerise\Integration\Helper\Config::XML_PATH_PAGE_TRACKING_KEY
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

    public function getCookieDomain()
    {
        $baseUrl = $this->storeManager->getStore()->getBaseUrl();
        return rtrim(preg_replace('#^https?://#', '.', $baseUrl), DIRECTORY_SEPARATOR);
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

    public function getCurrentTime()
    {
        return (new \DateTime())->format(self::FORMAT_ISO_8601);
    }

    public function formatDateTimeAsIso8601(\DateTime $dateTime)
    {
        return $dateTime->format(self::FORMAT_ISO_8601);
    }

    public function getCurrencyCode()
    {
        return $this->storeManager->getStore()->getCurrentCurrency()->getCode();
    }

    public function getSource()
    {
        $userAgent = $this->_httpHeader->getHttpUserAgent();
        $server = $this->_request->getServer();

        return \Zend_Http_UserAgent_Mobile::match($userAgent, $server) ? "WEB_MOBILE" : "WEB_DESKTOP";
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
            throw new \InvalidArgumentException('Invalid event');
        }

        return \Synerise\Integration\Model\Config\Source\EventTracking\Events::OPTIONS[$event];
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return array
     * @throws \Exception
     */
    public function prepareParamsfromQuoteProduct($product)
    {
        $sku = $product->getData('sku');
        $skuVariant = $product->getSku();

        $params = [
            "source" => $this->getSource(),
            "applicationName" => $this->getApplicationName(),
            "sku" => $sku,
            "name" => $product->getName(),
            "regularUnitPrice" => [
                "amount" => (float) $product->getPrice(),
                "currency" => $this->getCurrencyCode()
            ],
            "finalUnitPrice" => [
                "amount" => (float) $product->getFinalPrice(),
                "currency" => $this->getCurrencyCode()
            ],
            "productUrl" => $product->getUrlInStore(),
            "quantity" => $product->getQty()
        ];

        if ($sku!= $skuVariant) {
            $params['skuVariant'] = $skuVariant;
        }

        if ($product->getSpecialPrice()) {
            $params['discountedUnitPrice'] = [
                "amount" => $product->getSpecialPrice(),
                "currency" => $this->getCurrencyCode()
            ];
        }

        $categories = $product->getCategoryIds();
        if ($categories) {
            $params['categories'] = $categories;

            $category = $product->getCategoryId();
            if ($category) {
                $params['category'] = $category;
            }
        }

        if ($product->getImage()) {
            $imageUrl = $this->imageHelper->init($product, 'product_base_image')->getUrl();
            if ($imageUrl) {
                $params['image'] = $imageUrl;
            }
        }

        return $params;
    }

    public function manageClientUuid($email)
    {
        if ($this->isAdminStore()) {
            return;
        }

        $uuid = $this->getClientUuidFromCookie();
        $emailUuid = $this->genrateUuidByEmail($email);

        if ($uuid == $emailUuid) {
            // email uuid already set
            return;
        }

        // reset uuid via cookie
        $this->setClientUuidAndResetCookie((string) $emailUuid);

        $identityHash = $this->getCookieParams('identityHash');
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
            list ($body, $statusCode, $headers) = $this->apiHelper->getDefaultApiInstance()
                ->batchAddOrUpdateClientsWithHttpInfo('application/json', '4.4', $createAClientInCrmRequests);

            if ($statusCode != 202) {
                $this->_logger->error('Client update with uuid reset failed');
            }
        } catch (\Exception $e) {
            $this->_logger->error('Client update with uuid reset failed', ['exception' => $e]);
        }
    }

    protected function overflow32($v)
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

    protected function hashString($s)
    {
        $h = 0;
        $len = strlen($s);
        for ($i = 0; $i < $len; $i++) {
            $h = $this->overflow32(31 * $h + ord($s[$i]));
        }

        return $h;
    }

    public function genrateUuidByEmail($email)
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
        return $this->scopeResolver->getScope()->getCode() == \Magento\Store\Model\Store::ADMIN_CODE;
    }
}
