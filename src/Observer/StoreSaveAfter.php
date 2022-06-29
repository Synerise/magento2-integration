<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\ObserverInterface;

class StoreSaveAfter implements ObserverInterface
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Synerise\Integration\Model\ResourceModel\Cron\Status
     */
    protected $statusResourceModel;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Synerise\Integration\Model\ResourceModel\Cron\Status $statusResourceModel
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
