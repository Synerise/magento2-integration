<?php
namespace Synerise\Integration\Block\Tracking;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;
use Synerise\Integration\Helper\Tracking\Cookie;

class Code extends Template
{
    public const XML_PATH_PAGE_TRACKING_CUSTOM_ENABLED = 'synerise/page_tracking/custom_enabled';

    public const XML_PATH_PAGE_TRACKING_HOST = 'synerise/page_tracking/host';

    public const XML_PATH_PAGE_TRACKING_KEY = 'synerise/page_tracking/key';

    public const XML_PATH_PAGE_TRACKING_SCRIPT = 'synerise/page_tracking/script';

    public const XML_PATH_PAGE_TRACKING_CUSTOM_PAGE_VISIT = 'synerise/page_tracking/custom_page_visit';

    public const XML_PATH_DYNAMIC_CONTENT_VIRTUAL_PAGE = 'synerise/dynamic_content/virtual_page';

    /**
     * @var string
     */
    protected $trackerKey;

    /**
     * @var Cookie
     */
    protected $helper;

    /**
     * @param Context $context
     * @param Cookie $helper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Cookie $helper,
        array $data = []
    ) {
        $this->helper = $helper;

        parent::__construct($context, $data);
    }

    /**
     * Get Script options based on config values.
     *
     * @return string
     */
    public function getScriptOptions(): string
    {
        $options = [
            "'trackerKey': '{$this->_escaper->escapeHtmlAttr($this->getTrackerKey())}'"
        ];

        $cookieDomain = $this->_escaper->escapeUrl($this->getCookieDomain());
        if ($cookieDomain) {
            $options[] = "'domain': '$cookieDomain'";
        }

        $customPageVisit = $this->_scopeConfig->isSetFlag(
            self::XML_PATH_PAGE_TRACKING_CUSTOM_PAGE_VISIT,
            ScopeInterface::SCOPE_STORE
        );

        if ($customPageVisit) {
            $options[] = "'customPageVisit': true";
        }

        $virtualPage = $this->_scopeConfig->isSetFlag(
            self::XML_PATH_DYNAMIC_CONTENT_VIRTUAL_PAGE,
            ScopeInterface::SCOPE_STORE
        );

        if ($virtualPage) {
            $options[] = "'dynamicContent': { 'virtualPage': true}";
        }

        return implode(', ', $options);
    }

    /**
     * @inheritDoc
     */
    public function toHtml()
    {
        if ($this->isCustomScriptEnabled()) {
            $script = $this->getCustomTrackingScript();
            if ($script) {
                return $this->_escaper->escapeJs($script);
            }
        }

        return $this->getTrackerKey() ? parent::toHtml() : null;
    }

    /**
     * Get tracking host from config.
     *
     * @return string
     */
    public function getHost(): string
    {
        return $this->_escaper->escapeUrl(
            $this->_scopeConfig->getValue(
                self::XML_PATH_PAGE_TRACKING_HOST,
                ScopeInterface::SCOPE_STORE
            )
        );
    }
    
    /**
     * Get tracker key from config
     *
     * @return string
     */
    public function getTrackerKey(): string
    {
        if (!isset($this->trackerKey)) {
            $this->trackerKey = $this->_scopeConfig->getValue(
                self::XML_PATH_PAGE_TRACKING_KEY,
                ScopeInterface::SCOPE_STORE
            );
        }

        return $this->trackerKey;
    }

    /**
     * Check if custom tracking script is enabled
     *
     * @return bool
     */
    public function isCustomScriptEnabled(): bool
    {
        return $this->_scopeConfig->isSetFlag(
            self::XML_PATH_PAGE_TRACKING_CUSTOM_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get custom script escaped contents
     *
     * @return string|null
     */
    public function getCustomTrackingScript(): ?string
    {
        return $this->_scopeConfig->getValue(
            self::XML_PATH_PAGE_TRACKING_SCRIPT,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get escaped cookie domain
     *
     * @return string|null
     */
    protected function getCookieDomain(): ?string
    {
        try {
            return $this->_escaper->escapeHtml($this->helper->getCookieDomain());
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }
}
