<?php
namespace Synerise\Integration\Controller\Adminhtml\Log;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Controller\Adminhtml\System;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Helper\Log;

class Download extends System
{
    public const ADMIN_RESOURCE = 'Synerise_Integration::log_download';

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

    /**
     * @param Context $context
     * @param FileFactory $fileFactory
     * @param Log $logHelper
     * @param LoggerInterface $logger
     */
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

    /**
     * Execute
     *
     * @return ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $fileName = $this->getRequest()->getParam('filename');

        try {
            return $this->fileFactory->create(
                $fileName,
                [
                    'type'  => 'filename',
                    'value' => $this->logHelper->getLogFileAbsolutePath($fileName)
                ]
            );
        } catch (\Exception $e) {
            $this->logger->error($e);
            $this->messageManager->addErrorMessage("{$fileName} Download failed. Exception: ".$e->getMessage());
            $this->_redirect('synerise/log/index');
        }
    }
}
