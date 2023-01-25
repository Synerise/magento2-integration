<?php

namespace Synerise\Integration\Model\Synchronization;

use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\ResourceModel\Website\CollectionFactory as WebsiteCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Helper\Update\Client;
use Synerise\Integration\Model\AbstractSynchronization;
use Synerise\Integration\Model\ResourceModel\Cron\Queue as QueueResourceModel;

class Customer extends AbstractSynchronization
{
    const MODEL = 'customer';
    const ENTITY_ID = 'entity_id';

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var Client
     */
    protected $clientHelper;

    /**
     * @var Attribute
     */
    protected $eavAttribute;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

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
        StoreManagerInterface $storeManager,
        CollectionFactory $collectionFactory,
        Client $clientHelper,
        Attribute $eavAttribute
    ) {
        $this->eavAttribute = $eavAttribute;
        $this->clientHelper = $clientHelper;
        $this->websiteCollectionFactory = $websiteCollectionFactory;
        $this->storeManager = $storeManager;

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
            $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();
        }

        $collection = $this->collectionFactory->create();
        $collection->getSelect()->where('website_id=?', $websiteId);
        return $collection;
    }

    /**
     * @param $collection
     * @param int $storeId
     * @param int|null $websiteId
     * @return array|null
     * @throws \Magento\Framework\Exception\ValidatorException
     * @throws \Synerise\ApiClient\ApiException
     */
    public function sendItems($collection, int $storeId, ?int $websiteId = null): ?array
    {
        $collection->addAttributeToSelect(
            $this->clientHelper->getAttributesToSelect($storeId)
        );

        if (!$collection->getSize()) {
            return null;
        }

        $ids = [];
        $createAClientInCrmRequests = [];

        if (!$collection->count()) {
            return null;
        }

        foreach ($collection as $customer) {
            $ids[] = $customer->getEntityId();
            $createAClientInCrmRequests[] =
                $this->clientHelper->prepareCreateClientRequest(
                    $customer,
                    null,
                    $storeId
                );
        }

        if ($ids) {
            $response = $this->clientHelper->sendBatchAddOrUpdateClients($createAClientInCrmRequests, $storeId);
            $this->clientHelper->markAsSent($ids, $storeId);
            return $response;
        }

        return null;
    }

    public function markAllAsUnsent()
    {
        $this->connection->truncateTable($this->connection->getTableName('synerise_sync_customer'));
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
