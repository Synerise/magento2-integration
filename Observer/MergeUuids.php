<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\MessageQueue\Publisher\Event as EventPublisher;
use Synerise\Integration\Model\Config\Source\Debug\Exclude;
use Synerise\Integration\Model\Tracking\ConfigFactory;
use Synerise\Integration\SyneriseApi\Mapper\Data\CustomerMerge;
use Synerise\Integration\SyneriseApi\Sender\Data\Customer as CustomerSender;

class MergeUuids implements ObserverInterface
{
    public const EVENT = 'customer_merge_by_email';

    /**
     * @var Logger
     */
    protected $loggerHelper;

    /**
     * @var ConfigFactory
     */
    protected $configFactory;

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
     * @param ConfigFactory $configFactory
     * @param CustomerMerge $customerMerge
     * @param EventPublisher $eventPublisher
     * @param CustomerSender $customerSender
     */
    public function __construct(
        Logger $loggerHelper,
        ConfigFactory $configFactory,
        CustomerMerge $customerMerge,
        EventPublisher $eventPublisher,
        CustomerSender $customerSender
    ) {
        $this->loggerHelper = $loggerHelper;
        $this->configFactory = $configFactory;
        $this->customerMerge = $customerMerge;
        $this->eventPublisher = $eventPublisher;
        $this->customerSender = $customerSender;
    }

    /**
     * Merge uuids by email
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            $storeId = $observer->getData('storeId');

            $mergeRequest = $this->customerMerge->prepareRequest(
                $observer->getData('email'),
                $observer->getData('prevUuid'),
                $observer->getData('curUuid')
            );

            if ($this->configFactory->create($storeId)->isEventMessageQueueEnabled(self::EVENT)) {
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
}