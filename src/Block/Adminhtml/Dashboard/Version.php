<?php
namespace Synerise\Integration\Block\Adminhtml\Dashboard;

use Magento\Backend\Block\Template\Context;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\Module\ResourceInterface;

class Version extends \Magento\Backend\Block\Template
{
    /**
     * @var ResourceInterface
     */
    private $moduleResource;

    public function __construct(
        ResourceInterface $moduleResource,
        Context $context,
        array $data = [], ?JsonHelper
        $jsonHelper = null,
        ?DirectoryHelper $directoryHelper = null
    ) {
        $this->moduleResource = $moduleResource;

        parent::__construct($context, $data, $jsonHelper, $directoryHelper);
    }

    public function getVersion()
    {
        return $this->moduleResource->getDbVersion('Synerise_Integration');
    }
}
