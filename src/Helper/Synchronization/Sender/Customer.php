<?php

namespace Synerise\Integration\Helper\Synchronization\Sender;

use Magento\Customer\Model\ResourceModel\Customer\Collection;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;
use Synerise\Integration\Helper\Api\Factory\DefaultApiFactory;
use Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\Helper\Api\Update\Client;
use Synerise\Integration\Helper\Synchronization\Results;
use Synerise\Integration\Model\ApiConfig;

class Customer extends AbstractSender
{
    const MODEL = 'customer';
    const ENTITY_ID = 'entity_id';

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var DefaultApiFactory
     */
    protected $defaultApiFactory;

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

    public function __construct(
        StoreManagerInterface $storeManager,
        CollectionFactory $collectionFactory,
        Results $results,
        Synchronization $synchronization,
        LoggerInterface $logger,
        DefaultApiFactory $defaultApiFactory,
        Client $clientHelper,
        Attribute $eavAttribute,
        int $storeId,
        ApiConfig $apiConfig = null,
        ?int $websiteId = null
    ) {
        $this->logger = $logger;
        $this->defaultApiFactory = $defaultApiFactory;
        $this->eavAttribute = $eavAttribute;
        $this->clientHelper = $clientHelper;
        $this->storeManager = $storeManager;

        parent::__construct($results, $synchronization, $collectionFactory, $storeId, $apiConfig, $websiteId);
    }

    /**
     * @return Collection
     * @throws NoSuchEntityException
     */
    protected function createCollectionWithScope(): \Magento\Framework\Data\Collection
    {
        $collection = $this->collectionFactory->create();
        $collection->getSelect()->where('website_id=?', $this->getWebsiteId());
        return $collection;
    }

    /**
     * @param $collection
     * @return array|null
     * @throws ValidatorException
     * @throws ApiException
     */
    public function sendItems($collection): ?array
    {
        $collection->addAttributeToSelect(
            $this->clientHelper->getAttributesToSelect($this->getStoreId())
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
                    $this->getStoreId()
                );
        }

        if ($ids) {
            $response = $this->sendBatchAddOrUpdateClients($createAClientInCrmRequests);
            $this->results->markAsSent(self::MODEL, $ids, $this->getStoreId());
            return $response;
        }

        return null;
    }

    /**
     * @param CreateaClientinCRMRequest[] $createAClientInCrmRequests
     * @return array
     * @throws ApiException
     */
    public function sendBatchAddOrUpdateClients(array $createAClientInCrmRequests): array
    {
        list ($body, $statusCode, $headers) = $this->defaultApiFactory->create($this->getApiConfig())
            ->batchAddOrUpdateClientsWithHttpInfo('application/json', '4.4', $createAClientInCrmRequests);

        if (substr($statusCode, 0, 1) != 2) {
            throw new ApiException(sprintf('Invalid Status [%d]', $statusCode));
        } elseif ($statusCode == 207) {
            $this->logger->debug('Request accepted with errors', ['response' => $body]);
        }

        return [$body, $statusCode, $headers];
    }


    /**
     * @return int|null
     * @throws NoSuchEntityException
     */
    public function getWebsiteId(): ?int
    {
        if (!$this->websiteId) {
            $this->websiteId = $this->storeManager->getStore($this->getStoreId())->getWebsiteId();
        }
        return $this->websiteId;
    }
}
