<?php

namespace Synerise\Integration\Controller\Adminhtml\SearchIndex;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\PageFactory;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Model\SearchIndex\ErrorMessage;
use Synerise\Integration\Search\Container\Indices;
use Synerise\ItemsSearchConfigApiClient\ApiException;

class Assign extends Action
{
    /**
     * Authorization level
     */
    public const ADMIN_RESOURCE = 'Synerise_Integration::search_index_setup';

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var ErrorMessage
     */
    protected $errorMessage;

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
     * @param ErrorMessage $errorMessage
     * @param Indices $indices
     * @param Logger $logger
     */
    public function __construct(
        Action\Context $context,
        PageFactory $resultPageFactory,
        ErrorMessage $errorMessage,
        Indices $indices,
        Logger $logger
    ) {
        $this->resultPageFactory = $resultPageFactory;
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
        if ($storeId = $this->getRequest()->getParam('store')) {
            try {
                $this->indices->getIndices($storeId);

                $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
                $resultPage->getConfig()->getTitle()->prepend(__('Assign Search Index (Store: %1)', $storeId));
                return $resultPage;
            } catch (ApiException $exception) {
                $config = $this->errorMessage->getMessageFromConfigApiException($exception);
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
            } catch (\Exception $exception) {
                $this->logger->error($exception);
                $this->messageManager->addErrorMessage($this->errorMessage->getDefaultMessage());
            }
        }

        return $this->resultRedirectFactory->create()->setPath('*/*');
    }
}
