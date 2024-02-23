<?php

namespace Synerise\Integration\Helper\Tracking;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException;
use Magento\Framework\Stdlib\Cookie\FailureToSendException;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Helper\Tracking;
use Synerise\Integration\MessageQueue\Publisher\Event as EventPublisher;
use Synerise\Integration\Model\Config\Source\Debug\Exclude;
use Synerise\Integration\SyneriseApi\Mapper\CustomerMerge;
use Synerise\Integration\SyneriseApi\Sender\Data\Customer as CustomerSender;

class UuidManagement
{
    public const EVENT = 'customer_merge_by_email';

    /**
     * @var Cookie
     */
    protected $cookieHelper;

    /**
     * @var Logger
     */
    protected $loggerHelper;

    /**
     * @var Tracking
     */
    protected $trackingHelper;

    /**
     * @var UuidGenerator
     */
    protected $uuidGenerator;

    /**
     * @var CustomerMerge
     */
    protected $customerMerge;

    /**
     * @var EventPublisher
     */
    protected $eventPublisher;

    /**
     * @var CustomerSender
     */
    protected $customerSender;

    /**
     * @param Logger $loggerHelper
     * @param Cookie $cookieHelper
     * @param Tracking $trackingHelper
     * @param UuidGenerator $uuidGenerator
     * @param CustomerMerge $customerMerge
     * @param EventPublisher $eventPublisher
     * @param CustomerSender $customerSender
     */
    public function __construct(
        Logger $loggerHelper,
        Cookie $cookieHelper,
        Tracking $trackingHelper,
        UuidGenerator $uuidGenerator,
        CustomerMerge $customerMerge,
        EventPublisher $eventPublisher,
        CustomerSender $customerSender
    ) {
        $this->loggerHelper = $loggerHelper;
        $this->cookieHelper = $cookieHelper;
        $this->trackingHelper = $trackingHelper;
        $this->uuidGenerator = $uuidGenerator;
        $this->customerMerge = $customerMerge;
        $this->eventPublisher = $eventPublisher;
        $this->customerSender = $customerSender;
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

        // reset uuid via cookie
        try {
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

        $this->mergeUuids($email, $emailUuid, $uuid, $storeId);
    }

    /**
     * Merge uuids by email
     *
     * @param string $email
     * @param string $emailUuid
     * @param string $uuid
     * @param int $storeId
     * @return void
     */
    protected function mergeUuids(string $email, string $emailUuid, string $uuid, int $storeId)
    {
        try {
            $mergeRequest = $this->customerMerge->prepareRequest($email, $uuid, $emailUuid);

            if ($this->trackingHelper->isEventMessageQueueEnabled($storeId)) {
                $this->eventPublisher->publish(self::EVENT, $mergeRequest, $storeId);
            } else {
                $this->customerSender->batchAddOrUpdateClients($mergeRequest, $storeId, self::EVENT);
            }
        } catch (\Exception $e) {
            if (!$this->loggerHelper->isExcludedFromLogging(Exclude::EXCEPTION_CLIENT_MERGE_FAIL)) {
                $this->loggerHelper->error($e);
            }
        }
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
