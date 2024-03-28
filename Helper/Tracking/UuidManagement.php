<?php

namespace Synerise\Integration\Helper\Tracking;

use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException;
use Magento\Framework\Stdlib\Cookie\FailureToSendException;
use Synerise\Integration\Helper\Logger;

class UuidManagement
{
    /**
     * @var ManagerInterface
     */
    protected $eventManager;

    /**
     * @var Cookie
     */
    protected $cookieHelper;

    /**
     * @var Logger
     */
    protected $loggerHelper;

    /**
     * @var UuidGenerator
     */
    protected $uuidGenerator;

    /**
     * @param ManagerInterface $eventManager
     * @param Logger $loggerHelper
     * @param Cookie $cookieHelper
     * @param UuidGenerator $uuidGenerator
     */
    public function __construct(
        ManagerInterface $eventManager,
        Logger $loggerHelper,
        Cookie $cookieHelper,
        UuidGenerator $uuidGenerator
    ) {
        $this->eventManager = $eventManager;
        $this->loggerHelper = $loggerHelper;
        $this->cookieHelper = $cookieHelper;
        $this->uuidGenerator = $uuidGenerator;
    }

    /**
     * Manage client UUID by email & set reset UUID cookie if necessary
     *
     * @param string $email
     * @param int $storeId
     * @return void
     */
    public function manageByEmail(string $email, int $storeId)
    {
        $uuid = $this->cookieHelper->getSnrsUuid();
        if (!$uuid) {
            return;
        }

        $emailUuid = $this->uuidGenerator->generateByEmail($email);

        if ($uuid == $emailUuid) {
            // email uuid already set
            return;
        }

        try {
            // reset uuid via cookie
            $this->cookieHelper->setSnrsResetUuidCookie($emailUuid);
            $this->cookieHelper->setSnrsUuid($emailUuid);
        } catch (InputException|FailureToSendException|CookieSizeLimitReachedException|NoSuchEntityException $e) {
            $this->loggerHelper->error($e);
        }

        $identityHash = $this->cookieHelper->getSnrsP('identityHash');
        if ($identityHash && $identityHash != $this->hashString($email)) {
            // Different user, skip merge.
            return;
        }

        $this->eventManager->dispatch('synerise_merge_uuids', [
            'email' => $email,
            'curUuid' => $emailUuid,
            'prevUuid' => $uuid,
            'storeId' => $storeId,
        ]);

    }

    /**
     * Overflow 32
     *
     * @param int $v
     * @return int
     */
    protected function overflow32(int $v): int
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

    /**
     * Hash string
     *
     * @param string $s
     * @return int
     */
    protected function hashString(string $s): int
    {
        $h = 0;
        $len = strlen($s);
        for ($i = 0; $i < $len; $i++) {
            $h = $this->overflow32(31 * $h + ord($s[$i]));
        }

        return $h;
    }
}
