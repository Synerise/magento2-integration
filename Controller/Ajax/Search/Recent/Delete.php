<?php

namespace Synerise\Integration\Controller\Ajax\Search\Recent;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Microsoft\Kiota\Abstractions\ApiException;
use Psr\Log\LoggerInterface;
use Synerise\Api\Search\Models\DeletedSearch;
use Synerise\Api\Search\Search\V2\Indices\Item\DeletedSearches\DeletedSearchesRequestBuilderPostQueryParameters;
use Synerise\Api\Search\Search\V2\Indices\Item\DeletedSearches\DeletedSearchesRequestBuilderPostRequestConfiguration;
use Synerise\Integration\Api\SearchIndexRepositoryInterface;
use Synerise\Integration\Helper\Tracking\Cookie;
use Synerise\Integration\SyneriseApi\ConfigFactory as ApiConfigFactory;
use Synerise\Sdk\Api\ClientBuilderFactoryInterface;

class Delete implements HttpPostActionInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Cookie
     */
    private $cookieHelper;

    /**
     * @var ApiConfigFactory
     */
    private $apiConfigFactory;

    /**
     * @var ClientBuilderFactoryInterface
     */
    private $clientBuilderFactory;

    /**
     * @var SearchIndexRepositoryInterface
     */
    private $searchIndexRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        Cookie $cookieHelper,
        ApiConfigFactory $apiConfigFactory,
        ClientBuilderFactoryInterface $clientBuilderFactory,
        SearchIndexRepositoryInterface $searchIndexRepository,
        LoggerInterface $logger
    ) {
        $this->request = $context->getRequest();
        $this->resultFactory = $context->getResultFactory();
        $this->url = $context->getUrl();
        $this->messageManager = $context->getMessageManager();
        $this->storeManager = $storeManager;
        $this->cookieHelper = $cookieHelper;
        $this->apiConfigFactory = $apiConfigFactory;
        $this->clientBuilderFactory = $clientBuilderFactory;
        $this->searchIndexRepository = $searchIndexRepository;
        $this->logger = $logger;
    }

    public function execute()
    {
        $query = $this->request->getParam('query');
        if (!$query) {
            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setUrl($this->url->getBaseUrl());
            return $resultRedirect;
        }

        try {
            $this->doSoftDelete($query);
        } catch (\Exception $e) {
            if (!is_subclass_of($e, ApiException::class)) {
                $this->logger->error($e);
            }

            $this->messageManager->addErrorMessage(
                __('Unable to delete recent search.')
            );

            throw $e;
        }

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData([]);
        return $resultJson;
    }

    private function doSoftDelete(string $query)
    {
        $storeId = $this->storeManager->getStore()->getId();
        $indexId = $this->searchIndexRepository->getByStoreId($this->storeManager->getStore()->getId())->getIndexId();

        if (!$indexId) {
            throw new \InvalidArgumentException(sprintf('Suggestions index not set for store: %d.', $storeId));
        }

        $client = $this->clientBuilderFactory->create($this->apiConfigFactory->create($storeId));

        $request = new DeletedSearch();
        $request->setQuery($query);

        $requestConfiguration = new DeletedSearchesRequestBuilderPostRequestConfiguration(
            null,
            null,
            new DeletedSearchesRequestBuilderPostQueryParameters($this->cookieHelper->getSnrsUuid())
        );

        $client->search()->search()->v2()->indices()->byIndexId($indexId)->deletedSearches()
            ->post($request, $requestConfiguration)->wait();
    }
}