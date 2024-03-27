<?php
namespace Synerise\Integration\Block\Adminhtml\Log;

use Magento\Framework\View\Element\Template;

class Environment extends Template
{

    /**
     * Get url to download environment log
     *
     * @return string
     */
    public function getDownloadEnvironmentLogUrl(): string
    {
        return $this->_escaper->escapeUrl($this->getUrl('*/*/download_environment'));
    }
}
