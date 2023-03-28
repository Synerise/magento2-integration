<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Model\ResourceModel\Cron\Status;

class StoreSaveAfter implements ObserverInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Status
     */
    protected $statusResourceModel;

    public function __construct(
        LoggerInterface $logger,
        Status $statusResourceModel
    ) {
        $this->logger = $logger;
        $this->statusResourceModel = $statusResourceModel;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Store\Model\Store $store */
        $store = $observer->getEvent()->getStore();
        if (!$store->dataHasChangedFor('website_id')) {
            return;
        }

        try {
            $this->statusResourceModel->getConnection()->update(
                $this->statusResourceModel->getMainTable(),
                ['website_id' => $store->getWebsiteId()],
                ['store_id = ?' => $store->getId()]
            );
        } catch (\Exception $e) {
            $this->logger->error('Store data update failed', ['exception' => $e]);
        }
    }
}
