<?php
namespace Synerise\Integration\Block\Adminhtml\Module;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Synerise\Integration\Helper\Version as VersionHelper;

class Version extends Template
{

    /**
     * @var VersionHelper
     */
    private $versionHelper;

    /**
     * @param Context $context
     * @param VersionHelper $versionHelper
     * @param array $data
     * @param JsonHelper|null $jsonHelper
     * @param DirectoryHelper|null $directoryHelper
     */
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
     * Get current module version.
     *
     * @return string
     */
    public function getVersion(): string
    {
        return $this->_escaper->escapeHtmlAttr(
            $this->versionHelper->getMagentoModuleVersion('Synerise_Integration')
        );
    }
}
