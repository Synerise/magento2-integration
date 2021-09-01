<?php

namespace Synerise\Integration\Model\Synchronization;

use Magento\Eav\Model\ResourceModel\Entity\Attribute;
use Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Helper\Config;
use Synerise\Integration\Helper\Customer as CustomerHelper;
use Synerise\Integration\Model\AbstractSynchronization;


Class Customer extends AbstractSynchronization
{
    const MODEL = 'customer';
    const ENTITY_ID = 'entity_id';
    const CONFIG_XML_PATH_CRON_ENABLED = 'synerise/customer/cron_enabled';

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
    )
    {
        $this->eavAttribute = $eavAttribute;
        $this->customerHelper = $customerHelper;

        parent::__construct(
            $scopeConfig,
            $logger,
            $resource,
            $collectionFactory
        );
    }

    public function sendItems($collection, $status)
    {
        $attributes = $this->customerHelper->getAttributesToSelect();
        $collection->addAttributeToSelect($attributes);

        $this->customerHelper->addCustomersBatch($collection);
    }

    public function markAllAsUnsent()
    {
        $attributeId = $this->getSyneriseUpdatedAtAttributeId();
        if($attributeId) {
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
