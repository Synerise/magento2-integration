<?php

namespace Synerise\Integration\Controller\Adminhtml\SearchIndex;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\View\Result\PageFactory;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Model\SearchIndex;
use Synerise\Integration\Model\SearchIndex\ErrorMessage;
use Synerise\Integration\Model\Workspace\ConfigFactory as WorkspaceConfigFactory;
use Synerise\Integration\Search\Container\Indices;
use Synerise\Integration\SyneriseApi\ConfigFactory as ApiConfigFactory;
use Synerise\Integration\SyneriseApi\InstanceFactory as ApiInstanceFactory;
use Synerise\ItemsSearchConfigApiClient\Api\SearchConfigurationApi;
use Synerise\ItemsSearchConfigApiClient\ApiException;
use Synerise\ItemsSearchConfigApiClient\Model\Error;
use Synerise\ItemsSearchConfigApiClient\Model\SearchConfigSchema;

class Setup extends Action
{
    /**
     * Authorization level
     */
    public const ADMIN_RESOURCE = 'Synerise_Integration::search_index_setup';

    public const ERROR_ENTITY_NOT_FOUND = 'Search Index entity not found';

    public const ERROR_CONFIG_NOT_FOUND = 'API request failed. Search Index not found';

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

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
     * @var Indices
     */
    protected $indices;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param ApiConfigFactory $apiConfigFactory
     * @param ApiInstanceFactory $apiInstanceFactory
     * @param WorkspaceConfigFactory $workspaceConfigFactory
     * @param ErrorMessage $errorMessage
     * @param Indices $indices
     * @param Logger $logger
     */
    public function __construct(
        Action\Context $context,
        PageFactory $resultPageFactory,
        ApiConfigFactory $apiConfigFactory,
        ApiInstanceFactory $apiInstanceFactory,
        WorkspaceConfigFactory $workspaceConfigFactory,
        ErrorMessage $errorMessage,
        Indices $indices,
        Logger $logger
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->apiConfigFactory = $apiConfigFactory;
        $this->apiInstanceFactory = $apiInstanceFactory;
        $this->workspaceConfigFactory = $workspaceConfigFactory;
        $this->errorMessage = $errorMessage;
        $this->indices = $indices;
        $this->logger = $logger;

        parent::__construct($context);
    }

    /**
     * Edit Search Index
     *
     * @return \Magento\Framework\Controller\ResultInterface
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        if ($id = $this->getRequest()->getParam('id')) {
            $searchIndex = $this->loadSearchIndexEntity($id);

            if (!$searchIndex->getEntityId()) {
                $this->messageManager->addErrorMessage(self::ERROR_ENTITY_NOT_FOUND);
            } else {
                try {
                    $this->getIndex($searchIndex);

                    $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
                    $resultPage->getConfig()->getTitle()->prepend(__('Setup Search Index'));
                    return $resultPage;
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
            }
        }

        return $this->resultRedirectFactory->create()->setPath('*/*');
    }

    /**
     * @param $entityId
     * @return SearchIndex
     */
    protected function loadSearchIndexEntity($entityId): SearchIndex
    {
        $searchIndex = $this->_objectManager->create(SearchIndex::class);
        return $searchIndex->load($entityId);
    }

    /**
     * Get index
     *
     * @param SearchIndex $searchIndex
     * @return Error|SearchConfigSchema
     * @throws ApiException
     * @throws ValidatorException
     */
    protected function getIndex(SearchIndex $searchIndex)
    {
        return $this->getSearchConfigApiInstance($searchIndex->getStoreId())
            ->getIndexConfigV2($searchIndex->getIndexId());
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
