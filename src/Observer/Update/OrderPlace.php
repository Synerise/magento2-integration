<?php

namespace Synerise\Integration\Observer\Update;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\ValidatorException;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\Api\DefaultApi;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;
use Synerise\ApiClient\Model\CreateatransactionRequest;
use Synerise\Integration\Helper\Api;
use Synerise\Integration\Helper\Api\Factory\DefaultApiFactory;
use Synerise\Integration\Helper\Api\Identity;
use Synerise\Integration\Helper\Api\Update\Transaction;
use Synerise\Integration\Helper\Synchronization\Results;
use Synerise\Integration\Helper\Synchronization\Sender\Order as OrderSender;
use Synerise\Integration\Observer\AbstractObserver;

class OrderPlace  extends AbstractObserver implements ObserverInterface
{
    const EVENT = 'sales_order_place_after';

    /**
     * @var Api
     */
    protected $apiHelper;

    /**
     * @var DefaultApiFactory
     */
    private $defaultApiFactory;

    /**
     * @var Identity
     */
    protected $identityHelper;

    /**
     * @var Transaction
     */
    protected $transactionHelper;

    /**
     * @var Results
     */
    protected $resultsHelper;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        Api $apiHelper,
        DefaultApiFactory $defaultApiFactory,
        Identity $identityHelper,
        Transaction $transactionHelper,
        Results $resultsHelper
    ) {
        $this->apiHelper = $apiHelper;
        $this->defaultApiFactory = $defaultApiFactory;
        $this->identityHelper = $identityHelper;
        $this->transactionHelper = $transactionHelper;
        $this->resultsHelper = $resultsHelper;

        parent::__construct($scopeConfig, $logger);
    }

    public function execute(Observer $observer)
    {
        if (!$this->isEventTrackingEnabled(self::EVENT)) {
            return;
        }

        try {
            /** @var \Magento\Sales\Model\Order $order */
            $order = $observer->getEvent()->getOrder();

            $uuid = $this->identityHelper->getClientUuid();
            if ($uuid && $this->identityHelper->manageClientUuid($uuid, $order->getCustomerEmail())) {
                $this->sendMergeClients(
                    $this->identityHelper->prepareMergeClientsRequest(
                        $order->getCustomerEmail(),
                        $uuid,
                        $this->identityHelper->getClientUuid()
                    )
                );
            }

            $this->sendCreateTransaction(
                $this->transactionHelper->prepareCreateTransactionRequest(
                    $order,
                    $this->identityHelper->getClientUuid()
                ),
                $order->getStoreId()
            );

            $this->resultsHelper->markAsSent(OrderSender::MODEL, [$order->getEntityId()], $order->getStoreId());

            if (!$this->identityHelper->isAdminStore() && $order->getCustomerIsGuest()) {
                $this->sendCreateClient(
                    $this->transactionHelper->prepareCreateClientRequest(
                        $order,
                        $this->identityHelper->getClientUuid()
                    ),
                    $order->getStoreId()
                );
            }

        } catch (\Exception $e) {
            $this->logger->error('Synerise Api request failed', ['exception' => $e]);
        }
    }

    /**
     * @param CreateatransactionRequest $createTransactionRequest
     * @param int|null $storeId
     * @return array
     * @throws ApiException
     * @throws ValidatorException
     */
    public function sendCreateTransaction(CreateatransactionRequest $createTransactionRequest, ?int $storeId = null): array
    {
        list ($body, $statusCode, $headers) = $this->getDefaultApiInstance($storeId)
            ->createATransactionWithHttpInfo('4.4', $createTransactionRequest);

        if (substr($statusCode, 0, 1) != 2) {
            throw new ApiException(sprintf('Invalid Status [%d]', $statusCode));
        } elseif ($statusCode == 207) {
            $this->logger->debug('Request accepted with errors', ['response' => $body]);
        }

        return [$body, $statusCode, $headers];
    }

    /**
     * @param CreateaClientinCRMRequest $createAClientInCrmRequest
     * @param int|null $storeId
     * @return array
     * @throws ApiException
     * @throws ValidatorException
     */
    public function sendCreateClient(CreateaClientinCRMRequest $createAClientInCrmRequest, ?int $storeId = null): array
    {
        return $this->getDefaultApiInstance($storeId)
            ->createAClientInCrmWithHttpInfo('4.4', $createAClientInCrmRequest);
    }

    /**
     * @param CreateaClientinCRMRequest[] $createAClientInCrmRequests
     * @param int|null $storeId
     * @return void
     */
    public function sendMergeClients(array $createAClientInCrmRequests, ?int $storeId = null): array {

        try {
            list ($body, $statusCode, $headers) = $this->getDefaultApiInstance($storeId)
                ->batchAddOrUpdateClientsWithHttpInfo(
                    'application/json',
                    '4.4',
                    $createAClientInCrmRequests
                );

            if ($statusCode == 202) {
                return [$body, $statusCode, $headers];
            } else {
                $this->logger->error('Client update with uuid reset failed');
            }
        } catch (\Exception $e) {
            $this->logger->error('Client update with uuid reset failed', ['exception' => $e]);
        }
        return [null, null, null];
    }

    /**
     * @param int|null $storeId
     * @return DefaultApi
     * @throws ValidatorException
     * @throws ApiException
     */
    public function getDefaultApiInstance(?int $storeId = null): DefaultApi
    {
        return $this->defaultApiFactory->get($this->apiHelper->getApiConfigByScope($storeId));
    }
}
