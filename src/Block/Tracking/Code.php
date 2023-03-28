<?php
namespace Synerise\Integration\Block\Tracking;

use Magento\Store\Model\ScopeInterface;
use Synerise\Integration\Model\Config\Backend\Workspace;

class Code extends \Magento\Framework\View\Element\Template
{
    const XML_PATH_CUSTOM_ENABLED = 'synerise/page_tracking/custom_enabled';
    const XML_PATH_CUSTOM_PAGE_VISIT = 'synerise/page_tracking/custom_page_visit';
    const XML_PATH_VIRTUAL_PAGE = 'synerise/dynamic_content/virtual_page';
    const XML_PATH_PAGE_TRACKING_SCRIPT = 'synerise/page_tracking/script';

    public function getScriptOptions()
    {
        $options = [
            "'trackerKey': '{$this->getTrackerKey()}'"
        ];

        $cookieDomain = $this->getCookieDomain();
        if ($cookieDomain) {
            $options[] = "'domain': '$cookieDomain'";
        }

        if ($this->isCustomPageVisitEnabled()) {
            $options[] = "'customPageVisit': true";
        }

        if ($this->isVirtualPageEnabled()) {
            $options[] = "'dynamicContent': { 'virtualPage': true }";
        }

        return implode(', ', $options);
    }

    public function toHtml()
    {
        if ($this->isCustomScriptEnabled()) {
           $script = $this->getCustomTrackingScript();
           if ($script) {
               return $script;
           }
        }

        return $this->getTrackerKey() ? parent::toHtml() : null;
    }

    protected function getTrackerKey()
    {
        if (!isset($this->trackerKey)) {
            $this->trackerKey = $this->_scopeConfig->getValue(
                Workspace::XML_PATH_PAGE_TRACKING_KEY,
                ScopeInterface::SCOPE_STORE
            );
        }

        return $this->trackerKey;
    }

    protected function isCustomPageVisitEnabled()
    {
        return $this->_scopeConfig->isSetFlag(
            self::XML_PATH_CUSTOM_PAGE_VISIT,
            ScopeInterface::SCOPE_STORE
        );
    }

    protected function isCustomScriptEnabled()
    {
        return $this->_scopeConfig->isSetFlag(
            self::XML_PATH_CUSTOM_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    protected function isVirtualPageEnabled()
    {
        return $this->_scopeConfig->isSetFlag(
            self::XML_PATH_VIRTUAL_PAGE,
            ScopeInterface::SCOPE_STORE
        );
    }

    protected function getCustomTrackingScript()
    {
        return $this->_scopeConfig->getValue(
            self::XML_PATH_PAGE_TRACKING_SCRIPT,
            ScopeInterface::SCOPE_STORE
        );
    }

    protected function getCookieDomain()
    {
        return $this->_scopeConfig->getValue(
            Workspace::XML_PATH_PAGE_TRACKING_DOMAIN,
            ScopeInterface::SCOPE_STORE
        );
    }
}