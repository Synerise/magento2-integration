<?php

namespace Synerise\Integration\Model\Synchronization;

use Magento\Eav\Model\ResourceModel\Entity\Attribute;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Helper\Customer as CustomerHelper;
use Synerise\Integration\Model\AbstractSynchronization;

class Customer extends AbstractSynchronization
{
    const MODEL = 'customer';
    const ENTITY_ID = 'entity_id';
    const CONFIG_XML_PATH_CRON_ENABLED = 'synerise/customer/cron_enabled';

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var CustomerHelper
     */
    protected $customerHelper;

    /**
     * @var Attribute
     */
    protected $eavAttribute;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        ResourceConnection $resource,
        CollectionFactory $collectionFactory,
        CustomerHelper $customerHelper,
        Attribute $eavAttribute
    ) {
        $this->eavAttribute = $eavAttribute;
        $this->customerHelper = $customerHelper;

        parent::__construct(
            $scopeConfig,
            $logger,
            $resource,
            $collectionFactory
        );
    }

    /**
     * @param int $storeId
     * @param int|null $websiteId
     * @return \Magento\Customer\Model\ResourceModel\Customer\Collection
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function createCollectionWithScope($storeId, $websiteId = null)
    {
        if (!$websiteId) {
            $websiteId = $this->customerHelper->getWebsiteIdByStoreId($storeId);
        }

        $collection = $this->collectionFactory->create();
        $collection->getSelect()->where('website_id=?', $websiteId);
        return $collection;
    }

    public function sendItems($collection, $storeId, $websiteId = null)
    {
        $collection->addAttributeToSelect(
            $this->customerHelper->getAttributesToSelect($storeId)
        );

        $this->customerHelper->addCustomersBatch($collection, $storeId);
    }

    public function markAllAsUnsent()
    {
        $attributeId = $this->getSyneriseUpdatedAtAttributeId();
        if ($attributeId) {
            $this->connection->update(
                'customer_entity_datetime',
                ['value' => null],
                ['attribute_id', $attributeId]
            );
        }
    }

    public function getSyneriseUpdatedAtAttributeId()
    {
        return $this->eavAttribute->getIdByCode('customer', 'synerise_updated_at');
    }
}
