<?php
namespace Synerise\Integration\Block\Adminhtml\Log;

use Magento\Framework\View\Element\Template;

class Environment extends Template
{
    public function getDownloadEnvironmentLogUrl()
    {
        return $this->getUrl('*/*/download_environment');
    }
}