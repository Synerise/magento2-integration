<?php
namespace Synerise\Integration\Block\Tracking;

class Code extends \Magento\Framework\View\Element\Template
{
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Synerise\Integration\Helper\Tracking $helper,
        array $data = []
    ) {
        $this->helper = $helper;

        parent::__construct($context, $data);
    }

    public function getScriptOptions()
    {
        $options = [
            "'trackerKey': '{$this->helper->getTrackerKey()}'"
        ];

        $cookieDomain = $this->helper->getCookieDomain();
        if ($cookieDomain) {
            $options[] = "'domain': '$cookieDomain'";
        }

        $customPageVisit = (boolean) $this->_scopeConfig->isSetFlag(
            'synerise/page_tracking/custom_page_visit',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if ($customPageVisit) {
            $options[] = "'customPageVisit': $customPageVisit";
        }

        $virtualPage = (boolean) $this->_scopeConfig->isSetFlag(
            'synerise/dynamic_content/virtual_page',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if ($virtualPage) {
            $options[] = "'dynamicContent': { 'virtualPage': $virtualPage }";
        }

        return implode(', ', $options);
    }

    public function toHtml()
    {
        if ($this->helper->isCustomScriptEnabled()) {
           $script = $this->helper->getCustomTrackingScript();
           if ($script) {
               return $script;
           }
        }

        return $this->helper->getTrackerKey() ? parent::toHtml() : null;
    }
}