<?php

namespace Synerise\Integration\Observer\Update;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\ValidatorException;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\Api\DefaultApi;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;
use Synerise\Integration\Helper\Api;
use Synerise\Integration\Helper\Api\Factory\DefaultApiFactory;
use Synerise\Integration\Helper\Api\Identity;
use Synerise\Integration\Helper\Api\Update\Client;
use Synerise\Integration\Helper\Synchronization\Results;
use Synerise\Integration\Helper\Synchronization\Sender\Customer as CustomerSender;
use Synerise\Integration\Observer\AbstractObserver;

class CustomerSaveAfter  extends AbstractObserver implements ObserverInterface
{
    const EVENT = 'customer_save_after';

    const EXCLUDED_PATHS = [
        '/newsletter/manage/save/'
    ];

    protected $isSent = false;

    /**
     * @var Http
     */
    protected $request;

    /**
     * @var Api
     */
    protected $apiHelper;

    /**
     * @var DefaultApiFactory
     */
    protected $defaultApiFactory;

    /**
     * @var Client
     */
    protected $clientUpdate;

    /**
     * @var Results
     */
    protected $results;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        Http $request,
        Api $apiHelper,
        DefaultApiFactory $defaultApiFactory,
        Client $clientUpdate,
        Results $results
    ) {
        $this->request = $request;

        $this->apiHelper = $apiHelper;
        $this->defaultApiFactory = $defaultApiFactory;
        $this->clientUpdate = $clientUpdate;
        $this->results = $results;

        parent::__construct($scopeConfig, $logger);
    }

    public function execute(Observer $observer)
    {
        if (!$this->isEventTrackingEnabled(self::EVENT)) {
            return;
        }

        if ($this->isSent || in_array($this->request->getPathInfo(), self::EXCLUDED_PATHS)) {
            return;
        }

        try {
            $customer = $observer->getCustomer();

            list ($body, $statusCode, $headers) = $this->sendCreateClient(
                $this->clientUpdate->prepareCreateClientRequest(
                    $observer->getCustomer(),
                    Identity::generateUuidByEmail($customer->getEmail()),
                    $customer->getStoreId()
                ),
                $customer->getStoreId()
            );

            if ($statusCode == 202) {
                $this->markAsSent($customer);
            } else {
                $this->logger->error(
                    'Client update - invalid status',
                    [
                        'status' => $statusCode,
                        'body' => $body
                    ]
                );
            }
        } catch (\Exception $e) {
            $this->logger->error('Client update failed', ['exception' => $e]);
        }
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
     * @param int|null $storeId
     * @return DefaultApi
     * @throws ValidatorException
     * @throws ApiException
     */
    public function getDefaultApiInstance(?int $storeId = null): DefaultApi
    {
        return $this->defaultApiFactory->get($this->apiHelper->getApiConfigByScope($storeId));
    }

    /**
     * @param CustomerInterface $customer
     * @return void
     */
    protected function markAsSent(CustomerInterface $customer)
    {
        $this->isSent = true;
        $this->results->markAsSent(CustomerSender::MODEL, [$customer->getId()], $customer->getStoreId());
    }
}
