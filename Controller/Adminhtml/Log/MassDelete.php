<?php

namespace Synerise\Integration\Controller\Adminhtml\Log;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NotFoundException;
use Synerise\Integration\Helper\LogFile;
use Synerise\Integration\Helper\Logger;

class MassDelete extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level
     */
    public const ADMIN_RESOURCE = 'Synerise_Integration::log_delete';

    /**
     * @var LogFile
     */
    protected $logFileHelper;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Logger $logger
     * @param LogFile $logFileHelper
     */
    public function __construct(
        Context $context,
        Logger $logger,
        LogFile $logFileHelper
    ) {
        $this->logger = $logger;
        $this->logFileHelper = $logFileHelper;
        parent::__construct($context);
    }

    /**
     * Log mass delete action
     *
     * @return Redirect
     * @throws NotFoundException
     */
    public function execute(): Redirect
    {
        if (!$this->getRequest()->isPost()) {
            throw new NotFoundException(__('Page not found'));
        }

        $deleted = 0;
        $fileNames = $this->getRequest()->getParam('selected');
        foreach ($fileNames as $fileName) {
            try {
                // phpcs:ignore Magento2.Functions.DiscouragedFunction
                unlink($this->logFileHelper->getFileAbsolutePath($fileName));
                $deleted++;
            } catch (\Exception $e) {
                $this->logger->error($e);
                $this->messageManager->addErrorMessage("Failed do delete {$fileName}. Exception: ".$e->getMessage());
            }
        }

        $this->messageManager->addSuccessMessage(
            __('A total of %1 record(s) have been deleted.', $deleted)
        );

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('*/*/index');
    }
}
