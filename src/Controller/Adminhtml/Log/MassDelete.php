<?php

namespace Synerise\Integration\Controller\Adminhtml\Log;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NotFoundException;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Helper\Log;

class MassDelete extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level
     */
    public const ADMIN_RESOURCE = 'Synerise_Integration::log_delete';

    /**
     * @var Log
     */
    protected $logHelper;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Constructor
     *
     * @param Context $context
     * @param LoggerInterface $logger
     * @param Log $logHelper
     */
    public function __construct(
        Context $context,
        LoggerInterface $logger,
        Log $logHelper
    ) {
        $this->logger = $logger;
        $this->logHelper = $logHelper;
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
                unlink($this->logHelper->getLogFileAbsolutePath($fileName));
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
