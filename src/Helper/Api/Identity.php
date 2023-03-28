<?php

namespace Synerise\Integration\Helper\Api;

use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Framework\App\ScopeResolverInterface;
use Magento\Store\Model\Store;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;
use Synerise\Integration\Helper\Cookie;

class Identity
{
    /**
     * @var AdminSession
     */
    protected $adminSession;

    /**
     * @var ScopeResolverInterface
     */
    protected $scopeResolver;

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
        ScopeResolverInterface $scopeResolver,
        Cookie $cookieHelper
    ) {
        $this->adminSession = $adminSession;
        $this->scopeResolver = $scopeResolver;
        $this->cookieHelper = $cookieHelper;
    }

    /**
     * @return string|null
     */
    public function getClientUuid(): ?string
    {
        if ($this->isAdminStore()) {
            return null;
        }

        if (!$this->clientUuid) {
            $this->clientUuid = $this->cookieHelper->getClientUuid();
        }
        return $this->clientUuid;
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
    public function isAdminStore(): bool
    {
        return $this->adminSession->getUser() && $this->adminSession->getUser()->getId()
            || $this->scopeResolver->getScope()->getCode() == Store::ADMIN_CODE;
    }

    /**
     * @param string $uuid
     * @param string $email
     * @return bool
     */
    public function manageClientUuid(string $uuid, string $email): bool
    {
        $emailUuid = self::generateUuidByEmail($email);

        if ($uuid == $emailUuid) {
            // email uuid already set
            return false;
        }

        // reset uuid via cookie
        $this->cookieHelper->setResetUuidCookie($emailUuid);
        $this->clientUuid = $emailUuid;

        $identityHash = $this->cookieHelper->getCookieParams('identityHash');
        if ($identityHash && $identityHash != self::hashString($email)) {
            // Different user, skip merge.
            return false;
        }

        return true;
    }

    /**
     * @param string $email
     * @param string $uuid
     * @param string $emailUuid
     * @return CreateaClientinCRMRequest[]
     */
    public function prepareMergeClientsRequest(string $email, string $uuid, string $emailUuid): array
    {
        return [
            new CreateaClientinCRMRequest([
                'email' => $email,
                'uuid' => $emailUuid
            ]),
            new CreateaClientinCRMRequest([
                'email' => $email,
                'uuid' => $uuid
            ])
        ];
    }

    private static function hashString($s): int
    {
        $h = 0;
        $len = strlen($s);
        for ($i = 0; $i < $len; $i++) {
            $h = self::overflow32(31 * $h + ord($s[$i]));
        }

        return $h;
    }

    private static function overflow32($v): int
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