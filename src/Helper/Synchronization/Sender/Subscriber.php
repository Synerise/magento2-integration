<?php

namespace Synerise\Integration\Helper\Synchronization\Sender;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Newsletter\Model\ResourceModel\Subscriber\Collection;
use Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\Api\DefaultApi;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;
use Synerise\Integration\Helper\Api\Factory\DefaultApiFactory;
use Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\Helper\Api\Update\ClientAgreement;
use Synerise\Integration\Helper\Synchronization\Results;
use Synerise\Integration\Helper\Synchronization\Sender\Subscriber as SubscriberSender;
use Synerise\Integration\Model\ApiConfig;
use Synerise\Integration\Model\Cron\Status;

class Subscriber extends AbstractSender
{
    const MODEL = 'subscriber';
    const ENTITY_ID = 'subscriber_id';

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ClientAgreement
     */
    protected $clientAgreementHelper;

    /**
     * @var DefaultApiFactory
     */
    protected $defaultApiFactory;

    public function __construct(
        LoggerInterface $logger,
        CollectionFactory $collectionFactory,
        Results $results,
        Synchronization $synchronization,
        ClientAgreement $clientAgreementHelper,
        DefaultApiFactory $defaultApiFactory,
        int $storeId,
        ApiConfig $apiConfig,
        ?int $websiteId = null
    ) {
        $this->logger = $logger;
        $this->clientAgreementHelper = $clientAgreementHelper;
        $this->defaultApiFactory = $defaultApiFactory;

        parent::__construct($results, $synchronization, $collectionFactory, $storeId, $apiConfig, $websiteId);
    }

    /**
     * @param Status $status
     * @return Collection
     * @throws LocalizedException
     */
    public function getCollectionFilteredByIdRange(Status $status): \Magento\Framework\Data\Collection
    {
        $collection = parent::getCollectionFilteredByIdRange($status)
            ->addFieldToSelect(['subscriber_email', 'subscriber_status', 'change_status_at']);

        return $collection;
    }

    /**
     * @return Collection
     */
    protected function createCollectionWithScope(): \Magento\Framework\Data\Collection
    {
        $collection = $this->collectionFactory->create();
        $collection->getSelect()
            ->where('main_table.store_id=?', $this->getStoreId());

        return $collection;
    }

    /**
     * @param Collection $collection
     * @return array|null
     * @throws ApiException
     * @throws ValidatorException
     */
    public function sendItems($collection): ?array
    {
        if (!$collection->count()) {
            return null;
        }

        $ids = [];
        $requests = [];
        foreach ($collection as $subscriber) {
            $ids[] = $subscriber->getId();
            $requests[] = $this->clientAgreementHelper->prepareSubscribeRequest($subscriber);
        }

        $response = $this->sendBatchAddOrUpdateClients($requests);
        $this->results->markAsSent(SubscriberSender::MODEL, $ids);

        return $response;
    }

    /**
     * @param CreateaClientinCRMRequest[] $createAClientInCrmRequests
     * @return array
     * @throws ApiException
     * @throws ValidatorException
     */
    public function sendBatchAddOrUpdateClients(array $createAClientInCrmRequests): array
    {
        list ($body, $statusCode, $headers) = $this->getDefaultApiInstance()
            ->batchAddOrUpdateClientsWithHttpInfo('application/json', '4.4', $createAClientInCrmRequests);

        if (substr($statusCode, 0, 1) != 2) {
            throw new ApiException(sprintf('Client agreements - Invalid Status [%d]', $statusCode));
        } elseif ($statusCode == 207) {
            $this->logger->debug('Client agreements - Request accepted with errors', ['response' => $body]);
        }

        return [$body, $statusCode, $headers];
    }

    /**
     * @param int|null $storeId
     * @return DefaultApi
     * @throws ValidatorException
     * @throws ApiException
     */
    public function getDefaultApiInstance(): DefaultApi
    {
        return $this->defaultApiFactory->get($this->getApiConfig());
    }

}
