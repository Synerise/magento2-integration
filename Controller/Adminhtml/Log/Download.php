<?php
namespace Synerise\Integration\Controller\Adminhtml\Log;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Controller\Adminhtml\System;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\FileSystemException;
use Synerise\Integration\Helper\LogFile;
use Synerise\Integration\Helper\Logger;

class Download extends System
{
    public const ADMIN_RESOURCE = 'Synerise_Integration::log_download';

    /**
     * @var FileFactory
     */
    protected $fileFactory;

    /**
     * @var LogFile
     */
    protected $logFileHelper;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param Context $context
     * @param FileFactory $fileFactory
     * @param LogFile $logFileHelper
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        FileFactory $fileFactory,
        LogFile $logFileHelper,
        Logger $logger
    ) {
        $this->fileFactory = $fileFactory;
        $this->logFileHelper = $logFileHelper;
        $this->logger = $logger;

        parent::__construct($context);
    }

    /**
     * Execute
     *
     * @return ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $fileName = $this->getRequest()->getParam('filename');

        try {
            if (!is_string($fileName)) {
                throw new FileSystemException(__('Invalid log file name.'));
            }

            return $this->fileFactory->create(
                $fileName,
                [
                    'type'  => 'filename',
                    'value' => $this->logFileHelper->getExistingFilePath($fileName)
                ]
            );
        } catch (\Exception $e) {
            $this->logger->error($e);
            $this->messageManager->addErrorMessage(__('Download failed. Exception: %1', $e->getMessage()));
            return $this->_redirect('synerise/log/index');
        }
    }
}
