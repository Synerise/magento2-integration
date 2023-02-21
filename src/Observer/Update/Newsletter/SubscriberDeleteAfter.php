<?php

namespace Synerise\Integration\Observer\Event\Newsletter;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\ValidatorException;
use Magento\Newsletter\Model\Subscriber;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\Api\DefaultApi;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;
use Synerise\Integration\Helper\Api;
use Synerise\Integration\Helper\Api\Factory\DefaultApiFactory;
use Synerise\Integration\Helper\Api\Update\ClientAgreement;
use Synerise\Integration\Observer\AbstractObserver;

class SubscriberDeleteAfter extends AbstractObserver implements ObserverInterface
{
    const EVENT = 'newsletter_subscriber_save_after';

    /**
     * @var Api
     */
    protected $apiHelper;

    /**
     * @var ClientAgreement
     */
    protected $clientAgreementHelper;

    /**
     * @var DefaultApiFactory
     */
    protected $defaultApiFactory;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        Api $apiHelper,
        ClientAgreement $clientAgreementHelper,
        DefaultApiFactory $defaultApiFactory
    ) {
        $this->apiHelper = $apiHelper;
        $this->clientAgreementHelper = $clientAgreementHelper;
        $this->defaultApiFactory = $defaultApiFactory;

        parent::__construct($scopeConfig, $logger);
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->isEventTrackingEnabled(self::EVENT)) {
            return;
        }

        /** @var Subscriber $subscriber */
        $subscriber = $observer->getEvent()->getDataObject();

        try {
            $this->sendCreateClient(
                $this->clientAgreementHelper->prepareUnsubscribeRequest($subscriber),
                $subscriber->getStoreId()
            );
        } catch (\Exception $e) {
            $this->logger->error('Failed to unsubscribe user', ['exception' => $e]);
        }
    }

    /**
     * @param CreateaClientinCRMRequest $createAClientInCrmRequest
     * @param int|null $storeId
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     * @throws ApiException
     * @throws ValidatorException
     */
    public function sendCreateClient(CreateaClientinCRMRequest $createAClientInCrmRequest, int $storeId = null): array
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
}
