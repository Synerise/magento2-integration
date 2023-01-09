<?php

namespace Synerise\Integration\Helper;

use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\ScopeResolverInterface;
use Magento\Framework\Exception\ValidatorException;
use Magento\Store\Model\Store;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;

class Identity
{
    /**
     * @var AdminSession
     */
    protected $adminSession;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var ScopeResolverInterface
     */
    protected $scopeResolver;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Api
     */
    private $apiHelper;

    /**
     * @var Cookie
     */
    private $cookieHelper;

    /**
     * @var string Current client Uuid stored in session cookie
     */
    protected $clientUuid;

    public function __construct(
        AdminSession $adminSession,
        CustomerSession $customerSession,
        ScopeResolverInterface $scopeResolver,
        LoggerInterface $logger,
        Api $apiHelper,
        Cookie $cookieHelper
    ) {
        $this->adminSession = $adminSession;
        $this->customerSession = $customerSession;
        $this->scopeResolver = $scopeResolver;
        $this->logger = $logger;

        $this->apiHelper = $apiHelper;
        $this->cookieHelper = $cookieHelper;
    }

    public function getClientUuid()
    {
        if ($this->isAdminStore()) {
            return null;
        }

        if (!$this->clientUuid) {
            $this->clientUuid = $this->cookieHelper->getClientUuid();
        }
        return $this->clientUuid;
    }

    public function manageClientUuid(string $uuid, string $email)
    {
        if ($uuid) {
            return false;
        }

        $emailUuid = self::generateUuidByEmail($email);

        if ($uuid == $emailUuid) {
            // email uuid already set
            return false;
        }

        // reset uuid via cookie
        $this->cookieHelper->setClientUuidAndResetCookie($emailUuid);

        $identityHash = $this->cookieHelper->getCookieParams('identityHash');
        if ($identityHash && $identityHash != Identity::hashString($email)) {
            // Different user, skip merge.
            return false;
        }

        return true;
    }

    /**
     * @param CreateaClientinCRMRequest $createAClientInCrmRequest
     * @param int|null $storeId
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     * @throws ApiException
     * @throws ValidatorException
     */
    public function sendCreateClient(CreateaClientinCRMRequest $createAClientInCrmRequest, int $storeId = null): array
    {
        return $this->apiHelper->getDefaultApiInstance($storeId)
            ->createAClientInCrmWithHttpInfo('4.4', $createAClientInCrmRequest);
    }

    public function mergeClients($email, $uuid, $emailUuid) {
        $createAClientInCrmRequests = [
            new CreateaClientinCRMRequest([
                'email' => $email,
                'uuid' => $emailUuid
            ]),
            new CreateaClientinCRMRequest([
                'email' => $email,
                'uuid' => $uuid
            ])
        ];

        try {
            list ($body, $statusCode, $headers) = $this->apiHelper->getDefaultApiInstance()
                ->batchAddOrUpdateClientsWithHttpInfo('application/json', '4.4', $createAClientInCrmRequests);

            if ($statusCode != 202) {
                $this->logger->error('Client update with uuid reset failed');
            }
        } catch (\Exception $e) {
            $this->logger->error('Client update with uuid reset failed', ['exception' => $e]);
        }
    }

    /**
     * @param string $email
     * @return string
     */
    public static function generateUuidByEmail(string $email): string
    {
        return (string) \Ramsey\Uuid\Uuid::uuid5('ea1c3a9d-64a6-45d0-a70c-d2a055f350d3', $email);
    }

    /**
     * Check if current context is admin store
     *
     * @return bool
     */
    public function isAdminStore()
    {
        return $this->adminSession->getUser() && $this->adminSession->getUser()->getId()
            || $this->scopeResolver->getScope()->getCode() == Store::ADMIN_CODE;
    }

    /**
     * Check if customer is logged in
     *
     * @return bool
     */
    public function isCustomerLoggedIn()
    {
        return $this->customerSession->isLoggedIn();
    }

    private static function hashString($s)
    {
        $h = 0;
        $len = strlen($s);
        for ($i = 0; $i < $len; $i++) {
            $h = self::overflow32(31 * $h + ord($s[$i]));
        }

        return $h;
    }

    private static function overflow32($v)
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
}