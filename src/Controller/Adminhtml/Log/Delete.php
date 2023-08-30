<?php
namespace Synerise\Integration\Controller\Adminhtml\Log;

use Psr\Log\LoggerInterface;
use Synerise\Integration\Helper\Log;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Controller\Adminhtml\System;
use Magento\Framework\App\Response\Http\FileFactory;

class Delete extends System
{
    const ADMIN_RESOURCE = 'Synerise_Integration::log_delete';

    /**
     * @var FileFactory
     */
    protected $fileFactory;
    /**
     * @var Log
     */
    protected $logHelper;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        Context $context,
        FileFactory $fileFactory,
        Log $logHelper,
        LoggerInterface $logger
    ) {
        $this->fileFactory = $fileFactory;
        $this->logHelper = $logHelper;
        $this->logger = $logger;

        parent::__construct($context);
    }

    public function execute()
    {
        $fileName = $this->getRequest()->getParam('filename');

        try {
            unlink($this->logHelper->getLogFileAbsolutePath($fileName));
            $this->messageManager->addSuccessMessage("{$fileName} deleted.");
        } catch (\Exception $e) {
            $this->logger->error($e);
            $this->messageManager->addErrorMessage("Failed do delete {$fileName}. Exception: ".$e->getMessage());
        }

        $this->_redirect('synerise/log/index');
    }
}