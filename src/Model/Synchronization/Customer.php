<?php

namespace Synerise\Integration\Model\Synchronization;

use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\ResourceModel\Website\CollectionFactory as WebsiteCollectionFactory;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Helper\Customer as CustomerHelper;
use Synerise\Integration\Model\AbstractSynchronization;
use Synerise\Integration\ResourceModel\Cron\Queue as QueueResourceModel;

class Customer extends AbstractSynchronization
{
    const MODEL = 'customer';
    const ENTITY_ID = 'entity_id';

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

    /**
     * @var WebsiteCollectionFactory
     */
    private $websiteCollectionFactory;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        ResourceConnection $resource,
        QueueResourceModel $queueResourceModel,
        WebsiteCollectionFactory $websiteCollectionFactory,
        CollectionFactory $collectionFactory,
        CustomerHelper $customerHelper,
        Attribute $eavAttribute
    ) {
        $this->eavAttribute = $eavAttribute;
        $this->customerHelper = $customerHelper;
        $this->websiteCollectionFactory = $websiteCollectionFactory;

        parent::__construct(
            $scopeConfig,
            $logger,
            $resource,
            $queueResourceModel,
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

    /**
     * @return array
     */
    public function getEnabledStores()
    {
        $allEnabledStoreIds = parent::getEnabledStores();
        $storeIds = [];

        $websites = $this->websiteCollectionFactory->create();
        foreach($websites as $website) {
            $storeId = $website->getDefaultStore()->getId();
            if (in_array($storeId, $allEnabledStoreIds)) {
                $storeIds[] = $storeId;
            }
        }

        return $storeIds;
    }
}
