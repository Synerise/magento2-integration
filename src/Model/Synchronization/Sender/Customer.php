<?php

namespace Synerise\Integration\Model\Synchronization\Sender;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Customer\Model\ResourceModel\Customer\Collection;
use Magento\Store\Model\ScopeInterface;
use Synerise\Integration\Helper\Customer as CustomerHelper;
use Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\Model\Synchronization\SenderInterface;

class Customer implements SenderInterface
{
    const MODEL = 'customer';
    const ENTITY_ID = 'entity_id';

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
     * @return void
     * @throws \Synerise\ApiClient\ApiException
     */
    public function sendItems($collection, int $storeId, ?int $websiteId = null)
    {
        $collection->addAttributeToSelect(
            $this->customerHelper->getAttributesToSelect($storeId)
        );

        $this->customerHelper->addCustomersBatch($collection, $storeId);
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
