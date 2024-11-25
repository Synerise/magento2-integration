<?php

namespace Synerise\Integration\Controller\Adminhtml\SearchIndex\Assign;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\ValidatorException;
use Magento\Store\Model\ScopeInterface;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Model\SearchIndex;
use Synerise\Integration\Model\SearchIndex\ErrorMessage;
use Synerise\Integration\Model\SearchIndex\Validator;
use Synerise\Integration\Model\Workspace\ConfigFactory as WorkspaceConfigFactory;
use Synerise\Integration\Search\Attributes\Config;
use Synerise\Integration\SyneriseApi\Catalogs\Config as CatalogsConfig;
use Synerise\Integration\SyneriseApi\ConfigFactory as ApiConfigFactory;
use Synerise\Integration\SyneriseApi\InstanceFactory as ApiInstanceFactory;
use Synerise\ItemsSearchConfigApiClient\Api\SearchConfigurationApi;
use Synerise\ItemsSearchConfigApiClient\ApiException;
use Synerise\ItemsSearchConfigApiClient\Model\Error;
use Synerise\ItemsSearchConfigApiClient\Model\PostIndexConfigV2Request;
use Synerise\ItemsSearchConfigApiClient\Model\SearchableAttributesSchema;
use Synerise\ItemsSearchConfigApiClient\Model\SearchConfigSchema;

class Save extends Action
{
    /**
     * Authorization level
     */
    public const ADMIN_RESOURCE = 'Synerise_Integration::search_index_setup';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ApiConfigFactory
     */
    private $apiConfigFactory;

    /**
     * @var ApiInstanceFactory
     */
    private $apiInstanceFactory;

    /**
     * @var WorkspaceConfigFactory
     */
    private $workspaceConfigFactory;

    /**
     * @var CatalogsConfig
     */
    private $catalogsConfig;

    /**
     * @var ErrorMessage
     */
    private $errorMessage;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param ApiConfigFactory $apiConfigFactory
     * @param ApiInstanceFactory $apiInstanceFactory
     * @param WorkspaceConfigFactory $workspaceConfigFactory
     * @param CatalogsConfig $catalogsConfig
     * @param ErrorMessage $errorMessage
     * @param Validator $validator
     * @param Logger $logger
     */
    public function __construct(
        Action\Context $context,
        ScopeConfigInterface $scopeConfig,
        ApiConfigFactory $apiConfigFactory,
        ApiInstanceFactory $apiInstanceFactory,
        WorkspaceConfigFactory $workspaceConfigFactory,
        CatalogsConfig $catalogsConfig,
        ErrorMessage $errorMessage,
        Validator $validator,
        Logger $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->apiConfigFactory = $apiConfigFactory;
        $this->apiInstanceFactory = $apiInstanceFactory;
        $this->workspaceConfigFactory = $workspaceConfigFactory;
        $this->catalogsConfig = $catalogsConfig;
        $this->errorMessage = $errorMessage;
        $this->validator = $validator;
        $this->logger = $logger;

        parent::__construct($context);
    }

    /**
     * Save action
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        $createIndex = $this->getRequest()->getPostValue('create_index');
        $storeId = $this->getRequest()->getPostValue('store_id');
        $indexId = $this->getRequest()->getPostValue('index_id');
        $indexName = $this->getRequest()->getPostValue('index_name');

        if ($storeId) {
            try {
                /** @var SearchIndex $searchIndex */
                $searchIndex = $this->_objectManager->create(SearchIndex::class)
                    ->load($storeId, 'store_id')
                    ->setStoreId($storeId);

                if ($createIndex) {
                    $searchIndex
                        ->setIndexName($indexName)
                        ->setItemsCatalogId($this->catalogsConfig->getCatalogId($storeId));
                } else {
                    $searchIndexConfig = $this->getIndex($indexId, $storeId);
                    $searchIndex
                        ->setItemsCatalogId($searchIndexConfig->getItemsCatalogId())
                        ->setIndexId($searchIndexConfig->getIndexId())
                        ->setIndexName($searchIndexConfig->getIndexName());
                }

                if ($this->validator->isValid($searchIndex)) {
                    if ($createIndex) {
                        $searchIndexConfig = $this->createIndex($searchIndex);
                    }

                    $searchIndex
                        ->setIndexId($searchIndexConfig->getIndexId())
                        ->save();

                    $this->messageManager->addSuccessMessage(__('Search Index successfully assigned.'));
                    return $resultRedirect->setPath('*/*/');
                } else {
                    foreach ($this->validator->getMessages() as $message) {
                        $this->messageManager->addErrorMessage($message);
                    }
                }
            } catch (ApiException $e) {
                $config = $this->errorMessage->getMessageFromConfigApiException($e, $searchIndex);
                if (isset($config['url'])) {
                    $this->messageManager->addComplexErrorMessage(
                        'messageWithUrl',
                        [
                            'message' => $config['base_message'] . ' ' . $config['url_message'],
                            'url' => $config['url'],
                        ]
                    );
                } else {
                    $this->messageManager->addErrorMessage($this->errorMessage->getDefaultMessage());
                }
            } catch (\Exception $e) {
                $this->logger->debug($e);
                $this->messageManager->addErrorMessage($this->errorMessage->getDefaultMessage());
            }
            return $resultRedirect->setPath('*/*/assign', ['store' => $storeId]);
        }
        return $resultRedirect->setPath('*/*');
    }

    /**
     * Prepare request
     *
     * @param string $indexName
     * @param bool $enabled
     * @param string $language
     * @param int $catalogId
     * @param array $displayable
     * @param array $searchable
     * @return PostIndexConfigV2Request
     */
    public function prepareRequest(
        string $indexName,
        bool $enabled,
        string $language,
        int $catalogId,
        array $displayable,
        array $searchable
    ): PostIndexConfigV2Request
    {
        return new PostIndexConfigV2Request([
            'index_name' =>  $indexName,
            'enabled' => $enabled,
            'language' => $language,
            'items_catalog_id' => (string) $catalogId,
            'displayable_attributes' => $displayable,
            'searchable_attributes' => new SearchableAttributesSchema($searchable)
        ]);
    }

    /**
     * @param int $storeId
     * @return mixed
     */
    protected function getStoreLocale(int $storeId)
    {
        return $this->scopeConfig->getValue(
            'general/locale/code',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Create index from entity
     *
     * @return Error|SearchConfigSchema
     * @throws ApiException
     * @throws ValidatorException
     */
    protected function createIndex(SearchIndex $searchIndex)
    {
        return $this->getSearchConfigApiInstance($searchIndex->getStoreId())
            ->postIndexConfigV2($this->prepareRequest(
                $searchIndex->getIndexName(),
                true,
                substr($this->getStoreLocale($searchIndex->getStoreId()), 0, 2),
                $searchIndex->getItemsCatalogId(),
                Config::REQUIRED_DISPLAYABLE,
                Config::REQUIRED_SEARCHABLE
            ));
    }

    /**
     * Get index
     *
     * @param string $indexId
     * @param int $storeId
     * @return Error|SearchConfigSchema
     * @throws ApiException
     * @throws ValidatorException
     */
    protected function getIndex(string $indexId, int $storeId)
    {
        return $this->getSearchConfigApiInstance($storeId)->getIndexConfigV2($indexId);
    }

    /**
     * Get AI Search Api instance
     *
     * @param int $storeId
     * @return SearchConfigurationApi
     * @throws ValidatorException
     * @throws ApiException
     */
    protected function getSearchConfigApiInstance(int $storeId): SearchConfigurationApi
    {
        return $this->apiInstanceFactory->createApiInstance(
            'search-config',
            $this->apiConfigFactory->create($storeId),
            $this->workspaceConfigFactory->create($storeId)
        );
    }
}
