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
use Synerise\Integration\Helper\Tracking\UuidManagement;
use Synerise\Integration\Model\Config\Source\Debug\Exclude;
use Synerise\Integration\Model\Workspace\ConfigFactory as WorkspaceConfigFactory;
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
     * @return void
     * @throws ApiException|ValidatorException
     */
    public function sendItems($collection, int $storeId, ?int $websiteId = null)
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
            if ($eventName == UuidManagement::EVENT) {
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
}
