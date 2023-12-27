<?php
namespace Synerise\Integration\Block\Adminhtml\Module;

use Magento\Backend\Block\Template\Context;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Synerise\Integration\Helper\Version as VersionHelper;

class Version extends \Magento\Backend\Block\Template
{

    /**
     * @var VersionHelper
     */
    private $versionHelper;

    public function __construct(
        Context $context,
        VersionHelper $versionHelper,
        array $data = [],
        ?JsonHelper $jsonHelper = null,
        ?DirectoryHelper $directoryHelper = null
    ) {
        $this->versionHelper = $versionHelper;

        parent::__construct($context, $data, $jsonHelper, $directoryHelper);
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->versionHelper->getMagentoModuleVersion('Synerise_Integration');
    }
}
