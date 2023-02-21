<?php

namespace Synerise\Integration\Observer\Event\Customer;

use Magento\Framework\App\Config\ScopeConfigInterface;
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
use Synerise\Integration\Helper\Api\Event\Client;
use Synerise\Integration\Observer\AbstractObserver;

abstract class AbstractCustomerEvent extends AbstractObserver implements ObserverInterface
{

    /**
     * @var Api
     */
    protected $apiHelper;

    /**
     * @var Client
     */
    protected $clientHelper;

    /**
     * @var DefaultApiFactory
     */
    protected $defaultApiFactory;

    /**
     * @var Identity
     */
    protected $identityHelper;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface      $logger,
        Api                  $apiHelper,
        Client               $clientHelper,
        DefaultApiFactory    $defaultApiFactory,
        Identity             $identityHelper
    ) {
        $this->apiHelper = $apiHelper;
        $this->clientHelper = $clientHelper;
        $this->defaultApiFactory = $defaultApiFactory;
        $this->identityHelper = $identityHelper;

        parent::__construct($scopeConfig, $logger);
    }

    abstract public function execute(Observer $observer);

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