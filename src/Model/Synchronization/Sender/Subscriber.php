<?php

namespace Synerise\Integration\Model\Synchronization\Sender;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Newsletter\Model\ResourceModel\Subscriber\Collection;
use Magento\Store\Model\ScopeInterface;
use Synerise\Integration\Helper\Customer as CustomerHelper;
use Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\Model\Synchronization\SenderInterface;

class Subscriber implements SenderInterface
{
    const MODEL = 'subscriber';
    const ENTITY_ID = 'subscriber_id';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var CustomerHelper
     */
    protected $customerHelper;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        CustomerHelper $customerHelper
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->customerHelper = $customerHelper;
    }

    /**
     * @param Collection $collection
     * @param int $storeId
     * @param int|null $websiteId
     * @throws \Synerise\ApiClient\ApiException
     */
    public function sendItems($collection, int $storeId, ?int $websiteId = null)
    {
        $this->customerHelper->addCustomerSubscriptionsBatch($collection, $storeId);
        $this->customerHelper->markSubscribersAsSent($collection->getAllIds());
    }

    /**
     * @param int|null $storeId
     * @return int
     */
    public function getPageSize(?int $storeId = null): int
    {
        return (int) $this->scopeConfig->getValue(
            Synchronization::XML_PATH_CRON_STATUS_PAGE_SIZE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
