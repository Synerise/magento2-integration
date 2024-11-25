<?php

namespace Synerise\Integration\Controller\Adminhtml\SearchIndex\Setup;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\ValidatorException;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Model\SearchIndex;
use Synerise\Integration\Model\SearchIndex\ErrorMessage;
use Synerise\Integration\Model\SearchIndex\Validator;
use Synerise\Integration\Model\Workspace\ConfigFactory as WorkspaceConfigFactory;
use Synerise\Integration\Search\Attributes\Config;
use Synerise\Integration\SyneriseApi\Catalogs\AttributeValidatorFactory;
use Synerise\Integration\SyneriseApi\ConfigFactory as ApiConfigFactory;
use Synerise\Integration\SyneriseApi\InstanceFactory as ApiInstanceFactory;
use Synerise\ItemsSearchConfigApiClient\Api\SearchConfigurationApi;
use Synerise\ItemsSearchConfigApiClient\ApiException;
use Synerise\ItemsSearchConfigApiClient\Model\Error;
use Synerise\ItemsSearchConfigApiClient\Model\FacetableAttributesSchema;
use Synerise\ItemsSearchConfigApiClient\Model\FilterableAttributesSchema;
use Synerise\ItemsSearchConfigApiClient\Model\PostIndexConfigV2Request;
use Synerise\ItemsSearchConfigApiClient\Model\SearchableAttributesSchema;
use Synerise\ItemsSearchConfigApiClient\Model\SearchConfigSchema;
use Synerise\ItemsSearchConfigApiClient\Model\SortableAttributesSchema;

class Save extends Action
{
    /**
     * Authorization level
     */
    public const ADMIN_RESOURCE = 'Synerise_Integration::search_index_setup';

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
     * @var ErrorMessage
     */
    private $errorMessage;

    /**
     * @var Config
     */
    private $attributeConfig;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var AttributeValidatorFactory
     */
    private $attributeValidatorFactory;

    /**
     * @param Context $context
     * @param ApiConfigFactory $apiConfigFactory
     * @param ApiInstanceFactory $apiInstanceFactory
     * @param WorkspaceConfigFactory $workspaceConfigFactory
     * @param ErrorMessage $errorMessage
     * @param Config $attributeConfig
     * @param Logger $logger
     * @param AttributeValidatorFactory $attributeValidatorFactory
     * @param Validator $validator
     */
    public function __construct(
        Action\Context $context,
        ApiConfigFactory $apiConfigFactory,
        ApiInstanceFactory $apiInstanceFactory,
        WorkspaceConfigFactory $workspaceConfigFactory,
        ErrorMessage $errorMessage,
        Config $attributeConfig,
        Logger $logger,
        AttributeValidatorFactory $attributeValidatorFactory,
        Validator $validator
    ) {
        $this->apiConfigFactory = $apiConfigFactory;
        $this->apiInstanceFactory = $apiInstanceFactory;
        $this->workspaceConfigFactory = $workspaceConfigFactory;
        $this->errorMessage = $errorMessage;
        $this->attributeConfig = $attributeConfig;
        $this->logger = $logger;
        $this->attributeValidatorFactory = $attributeValidatorFactory;
        $this->validator = $validator;

        parent::__construct($context);
    }

    /**
     * Save action
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        $data = $this->getRequest()->getPostValue();
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($data) {
            try {
                $entityId = $data['entity_id'];

                /** @var SearchIndex $searchIndex */
                $searchIndex = $this->_objectManager->create(SearchIndex::class);
                $searchIndex->load($entityId);
                if ($this->validator->isValid($searchIndex)) {
                    $this->updateIndex($searchIndex->getIndexId(), $searchIndex->getStoreId(), $data);
                    $this->messageManager->addSuccessMessage(__('Search Index successfully saved.'));
                } else {
                    foreach ($this->validator->getMessages() as $message) {
                        $this->messageManager->addErrorMessage($message);
                    }
                }
                return $resultRedirect->setPath('*/*');
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

            return $resultRedirect->setPath('*/*/setup', ['id' => $entityId]);
        }

        return $resultRedirect->setPath('*/*/');
    }

    /**
     * @param string $searchConfigId
     * @param int $storeId
     * @param array $formData
     * @return Error|SearchConfigSchema
     * @throws ApiException
     * @throws ValidatorException
     */
    protected function updateIndex(string $searchConfigId, int $storeId, array $formData)
    {
        $searchConfig = $this->getSearchConfigApiInstance($storeId)->getIndexConfigV2($searchConfigId);
        $attributeValidator = $this->attributeValidatorFactory->create($searchConfig->getItemsCatalogId(), $storeId);
        
        $searchable = $attributeValidator->getValid($formData['searchable']);
        $sortable = [
            'text' => $attributeValidator->getValid($formData['sortable']),
            'range' => []
        ];
         $facetable = [
             'text' => array_merge(
                 $attributeValidator->getValid($formData['filterable']),
                 $this->attributeConfig->getFacetableRequired()['text']
             ),
             'range' => $this->attributeConfig->getFacetableRequired()['range']
        ];
        $filterable = [
            'text' => array_merge($facetable['text'], $this->attributeConfig->getFilterableRequired()['text']),
            'range' => array_merge($facetable['range'], $this->attributeConfig->getFilterableRequired()['range'])
        ];

        $unavailable = $attributeValidator->getUnavailableAttributes();
        if ($unavailable) {
            $this->messageManager->addWarningMessage(
                __('Skipping attributes that are not present in catalog: %1', implode(', ', $unavailable)));
        }

        return $this->getSearchConfigApiInstance($storeId)
            ->updateIndexConfigV2($searchConfigId, $this->prepareRequest(
                $searchConfig,
                Config::REQUIRED_DISPLAYABLE,
                $searchable,
                $sortable,
                $filterable,
                $facetable
            ));
    }

    public function prepareRequest(
        SearchConfigSchema $searchConfig,
        array $displayable,
        array $searchable,
        array $sortable,
        array $filterable,
        array $facetable
    ): PostIndexConfigV2Request {
        return new PostIndexConfigV2Request([
            'enabled' => $searchConfig->getEnabled(),
            'language' => $searchConfig->getLanguage(),
            'index_name' => $searchConfig->getIndexName(),
            'items_catalog_id' => $searchConfig->getItemsCatalogId(),
            'displayable_attributes' => $displayable,
            'searchable_attributes' => $this->prepareSearchableAttributes($searchConfig, $searchable),
            'sortable_attributes' => new SortableAttributesSchema($sortable),
            'filterable_attributes' => new FilterableAttributesSchema($filterable),
            'facetable_attributes' => new FacetableAttributesSchema($facetable)
        ]);
    }

    /**
     * Prepare searchable attributes keeping their original weight
     *
     * @param SearchConfigSchema $searchConfig
     * @param array $requested
     * @return SearchableAttributesSchema
     */
    protected function prepareSearchableAttributes(SearchConfigSchema $searchConfig, array $requested): SearchableAttributesSchema
    {
        $previous = [
            'fulltext' => $searchConfig->getSearchableAttributes()->getFullText(),
            'fulltext_boosted' => $searchConfig->getSearchableAttributes()->getFullTextBoosted(),
            'full_text_strongly_boosted' => $searchConfig->getSearchableAttributes()->getFullTextStronglyBoosted(),
        ];

        $current = [];
        foreach($requested as $fieldName) {
            $found = false;
            foreach($previous as $type => $selected) {
                if (isset($selected[$fieldName])) {
                    $found = true;
                    $current[$type] = $fieldName;
                    break;
                }
            }
            if (!$found) {
                $current['full_text'][] = $fieldName;
            }
        }

        return new SearchableAttributesSchema($current);
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
