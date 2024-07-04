<?php

namespace Synerise\Integration\SyneriseApi\Sender\Data;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Model\Customer as CustomerModel;
use Magento\Customer\Model\ResourceModel\Customer\Collection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\ValidatorException;
use Synerise\ApiClient\Api\DefaultApi;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Model\Config\Source\Debug\Exclude;
use Synerise\Integration\Model\Workspace\ConfigFactory as WorkspaceConfigFactory;
use Synerise\Integration\Observer\MergeUuids;
use Synerise\Integration\SyneriseApi\Mapper\Data\CustomerCRUD;
use Synerise\Integration\SyneriseApi\Sender\AbstractSender;
use Synerise\Integration\Model\Config\Source\Customers\Attributes;
use Synerise\Integration\SyneriseApi\ConfigFactory;
use Synerise\Integration\SyneriseApi\InstanceFactory;

class Customer extends AbstractSender implements SenderInterface
{
    public const MODEL = 'customer';

    public const ENTITY_ID = 'entity_id';

    /**
     * @var AddressRepositoryInterface
     */
    protected $addressRepository;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var CustomerCRUD
     */
    protected $customerCRUD;

    /**
     * @param ResourceConnection $resource
     * @param Logger $loggerHelper
     * @param ConfigFactory $configFactory
     * @param InstanceFactory $apiInstanceFactory
     * @param WorkspaceConfigFactory $workspaceConfigFactory
     * @param CustomerCRUD $customerCRUD
     */
    public function __construct(
        ResourceConnection $resource,
        Logger $loggerHelper,
        ConfigFactory $configFactory,
        InstanceFactory $apiInstanceFactory,
        WorkspaceConfigFactory $workspaceConfigFactory,
        CustomerCRUD $customerCRUD
    ) {
        $this->resource = $resource;
        $this->customerCRUD = $customerCRUD;

        parent::__construct($loggerHelper, $configFactory, $apiInstanceFactory, $workspaceConfigFactory);
    }

    /**
     * Send Items
     *
     * @param Collection|CustomerModel[] $collection
     * @param int $storeId
     * @param int|null $websiteId
     * @param array $options
     * @return void
     * @throws ApiException|ValidatorException
     */
    public function sendItems($collection, int $storeId, ?int $websiteId = null, array $options = [])
    {
        $createAClientInCrmRequests = [];
        $ids = [];

        foreach ($collection as $customer) {
            $createAClientInCrmRequests[] = $this->customerCRUD->prepareRequest($customer, $storeId);
            $ids[] = $customer->getEntityId();
        }

        if (!empty($createAClientInCrmRequests)) {
            $this->batchAddOrUpdateClients(
                $createAClientInCrmRequests,
                $storeId
            );
            $this->markCustomersAsSent($ids, $storeId);
        }
    }

    /**
     * Batch add or update clients
     *
     * @param mixed $payload
     * @param int $storeId
     * @param string|null $eventName
     * @throws ApiException
     * @throws ValidatorException
     */
    public function batchAddOrUpdateClients($payload, int $storeId, string $eventName = null)
    {
        try {
            list ($body, $statusCode, $headers) = $this->sendWithTokenExpiredCatch(
                function () use ($storeId, $payload) {
                    $this->getDefaultApiInstance($storeId)
                        ->batchAddOrUpdateClientsWithHttpInfo('application/json', '4.4', $payload);
                },
                $storeId
            );

            if ($statusCode == 207) {
                $this->loggerHelper->warning('Request partially accepted', ['response_body' => $body]);
            }
        } catch (ApiException $e) {
            $shouldLogException = true;
            if ($eventName == MergeUuids::EVENT) {
                $shouldLogException = $this->loggerHelper->isExcludedFromLogging(
                    Exclude::EXCEPTION_CLIENT_MERGE_FAIL
                );
            }

            if ($shouldLogException) {
                $this->logApiException($e);
            }
            throw $e;
        }
    }

    /**
     * Delete item
     *
     * @param $payload
     * @param int $storeId
     * @param int|null $entityId
     * @return void
     * @throws ApiException
     * @throws ValidatorException
     */
    public function deleteItem($payload, int $storeId, ?int $entityId = null)
    {
        list ($body, $statusCode, $headers) = $this->sendWithTokenExpiredCatch(
            function () use ($storeId, $payload) {
                $this->getDefaultApiInstance($storeId)
                    ->deleteAClientByCustomIdWithHttpInfo($payload, 'application/json', '4.4');
            },
            $storeId
        );

        if ($entityId) {
            $this->deleteStatus([$entityId], $storeId);
        }
    }

    /**
     * Get default API instance
     *
     * @param int $storeId
     * @return DefaultApi
     * @throws ApiException
     * @throws ValidatorException
     */
    protected function getDefaultApiInstance(int $storeId): DefaultApi
    {
        return $this->getApiInstance('default', $storeId);
    }

    /**
     * @inheritDoc
     */
    public function getAttributesToSelect(int $storeId): array
    {
        return array_merge(
            $this->customerCRUD->getEnabledAttributes($storeId),
            Attributes::REQUIRED
        );
    }

    /**
     * Mark customers as sent
     *
     * @param int[] $ids
     * @return void
     * @param int $storeId
     */
    public function markCustomersAsSent(array $ids, int $storeId = 0)
    {
        $data = [];
        foreach ($ids as $id) {
            $data[] = [
                'customer_id' => $id,
                'store_id' => $storeId
            ];
        }
        $this->resource->getConnection()->insertOnDuplicate(
            $this->resource->getTableName('synerise_sync_customer'),
            $data
        );
    }

    /**
     * Delete status
     *
     * @param int[] $entityIds
     * @param int $storeId
     * @return void
     */
    protected function deleteStatus(array $entityIds, int $storeId)
    {
        $this->resource->getConnection()->delete(
            $this->resource->getConnection()->getTableName('synerise_sync_customer'),
            [
                'store_id = ?' => $storeId,
                'customer_id IN (?)' => $entityIds,
            ]
        );
    }
}
