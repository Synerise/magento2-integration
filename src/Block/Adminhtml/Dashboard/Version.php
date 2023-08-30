<?php
namespace Synerise\Integration\Block\Adminhtml\Dashboard;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Model\UrlInterface;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Synerise\Integration\Helper\Version as VersionHelper;

class Version extends \Magento\Backend\Block\Template
{
    /**
     * @var UrlInterface
     */
    private $backendUrl;

    /**
     * @var VersionHelper
     */
    private $versionHelper;

    public function __construct(
        Context $context,
        UrlInterface $backendUrl,
        VersionHelper $versionHelper,
        array $data = [], ?JsonHelper
        $jsonHelper = null,
        ?DirectoryHelper $directoryHelper = null
    ) {
        $this->backendUrl = $backendUrl;
        $this->versionHelper = $versionHelper;

        parent::__construct($context, $data, $jsonHelper, $directoryHelper);
    }

    public function getVersion()
    {
        return $this->versionHelper->getMagentoModuleVersion('Synerise_Integration');
    }

    public function getEnvironmentLogUrl(): string
    {
        return $this->backendUrl->getUrl('synerise/dashboard/download_environment');
    }
}
