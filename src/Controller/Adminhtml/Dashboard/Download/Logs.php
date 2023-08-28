<?php
namespace Synerise\Integration\Controller\Adminhtml\Dashboard\Download;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Controller\Adminhtml\System;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\Exception\NotFoundException;

class Logs extends System
{
    const ADMIN_RESOURCE = 'Synerise_Integration::dashboard';

    const FILENAME = 'synerise.log';

    /**
     * @var FileFactory
     */
    protected $fileFactory;

    /**
     * @var Filesystem\DirectoryList
     */
    private $directoryList;

    public function __construct(Context $context, FileFactory $fileFactory, Filesystem\DirectoryList $directoryList)
    {
        $this->fileFactory = $fileFactory;
        $this->directoryList = $directoryList;

        parent::__construct($context);
    }

    public function execute()
    {
        try {
            return $this->fileFactory->create(
                self::FILENAME,
                [
                    'type'  => 'filename',
                    'value' => $this->getFilePath()
                ]
            );
        } catch (\Exception $e) {
            throw new NotFoundException(__($e->getMessage()));
        }
    }

    protected function getFilePath()
    {
        return $this->directoryList->getPath(DirectoryList::LOG) . DIRECTORY_SEPARATOR . self::FILENAME ;
    }
}