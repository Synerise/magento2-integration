<?php
namespace Synerise\Integration\Block\Adminhtml\Dashboard;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Model\UrlInterface;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Synerise\Integration\Controller\Adminhtml\Dashboard\Download\Logs;
use Synerise\Integration\Helper\Version as VersionHelper;

class Version extends \Magento\Backend\Block\Template
{
    /**
     * @var UrlInterface
     */
    private $backendUrl;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var File
     */
    private $fileDriver;

    /**
     * @var VersionHelper
     */
    private $versionHelper;

    public function __construct(
        Context $context,
        UrlInterface $backendUrl,
        DirectoryList $directoryList,
        File $fileDriver,
        VersionHelper $versionHelper,
        array $data = [], ?JsonHelper
        $jsonHelper = null,
        ?DirectoryHelper $directoryHelper = null
    ) {
        $this->backendUrl = $backendUrl;
        $this->directoryList = $directoryList;
        $this->fileDriver = $fileDriver;
        $this->versionHelper = $versionHelper;

        parent::__construct($context, $data, $jsonHelper, $directoryHelper);
    }

    public function getVersion()
    {
        $this->versionHelper->getMagentoModuleVersion('Synerise_Integration');
    }

    public function getLogsUrl()
    {
        return $this->backendUrl->getUrl('synerise/dashboard/download_logs');
    }

    public function getLogsLink()
    {
        if ($this->fileExists(Logs::FILEPATH)) {
            return '<a href="' . $this->getLogsUrl() .'">logs</a>';
        }

        return 'logs';
    }

    public function getEnvironmentUrl()
    {
        return $this->backendUrl->getUrl('synerise/dashboard/download_environment');
    }

    public function getEnvironmentLink()
    {
        return '<a href="' . $this->getEnvironmentUrl() .'">environment</a>';
    }

    protected function fileExists($path)
    {
        $root = $this->directoryList->getRoot();
        try {
            return $this->fileDriver->isExists($root . ($root && $path ? '/' : '') . $path);
        } catch (FileSystemException $e) {
            return false;
        }
    }
}
