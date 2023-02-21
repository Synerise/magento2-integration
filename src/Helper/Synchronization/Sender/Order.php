<?php

namespace Synerise\Integration\Helper\Synchronization\Sender;

use Exception;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CreateatransactionRequest;
use Synerise\Integration\Helper\Api\Factory\DefaultApiFactory;
use Synerise\Integration\Helper\Api\Identity;
use Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\Helper\Api\Update\Transaction;
use Synerise\Integration\Helper\Synchronization\Results;
use Synerise\Integration\Model\ApiConfig;

class Order extends AbstractSender
{
    const MODEL = 'order';

    const ENTITY_ID = 'entity_id';

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var DefaultApiFactory
     */
    protected $defaultApiFactory;

    /**
     * @var Transaction
     */
    protected $transactionHelper;

    public function __construct(
        CollectionFactory $collectionFactory,
        Results $results,
        Synchronization $synchronization,
        DateTime $dateTime,
        LoggerInterface $logger,
        DefaultApiFactory $defaultApiFactory,
        Transaction $transactionHelper,
        int $storeId,
        ApiConfig $apiConfig,
        ?int $websiteId = null
    ) {
        $this->dateTime = $dateTime;
        $this->logger = $logger;
        $this->defaultApiFactory = $defaultApiFactory;
        $this->transactionHelper = $transactionHelper;

        parent::__construct($results, $synchronization, $collectionFactory, $storeId, $apiConfig, $websiteId);
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
     * @param $collection
     * @return void
     * @throws ValidatorException
     * @throws ApiException
     * @throws Exception
     */
    public function sendItems($collection): ?array
    {
        $collection->addAttributeToSelect('*');

        if (!$collection->getSize()) {
            return null;
        }

        $ids = [];
        $createTransactionRequest = [];
        foreach ($collection as $order) {
            $ids[] = $order->getId();
            $email = $order->getCustomerEmail();
            $uuid = $email ? Identity::generateUuidByEmail($email): null;
            $request = $this->transactionHelper->prepareCreateTransactionRequest($order, $uuid);
            if ($request) {
                $createTransactionRequest[] = $request;
            }
        }

        if (!empty($createTransactionRequest)) {
            $response = $this->sendBatchAddOrUpdateTransactions($createTransactionRequest);
            $this->results->markAsSent(self::MODEL, $ids, $this->getStoreId());
            return $response;
        }

        return null;
    }

    /**
     * @param CreateatransactionRequest[] $createTransactionRequest
     * @return array
     * @throws ApiException
     */
    public function sendBatchAddOrUpdateTransactions(array $createTransactionRequest): array
    {
        list ($body, $statusCode, $headers) = $this->defaultApiFactory->create($this->getApiConfig())
            ->batchAddOrUpdateTransactionsWithHttpInfo('4.4', $createTransactionRequest);

        if (substr($statusCode, 0, 1) != 2) {
            throw new ApiException(sprintf('Invalid Status [%d]', $statusCode));
        } elseif ($statusCode == 207) {
            $this->logger->debug('Request accepted with errors', ['response' => $body]);
        }

        return [$body, $statusCode, $headers];
    }
}
