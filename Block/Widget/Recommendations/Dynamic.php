<?php

namespace Synerise\Integration\Block\Widget\Recommendations;

use Magento\Framework\View\Element\Template;
use Magento\Framework\Serialize\Serializer\Json;

class Dynamic extends Template
{
    /**
     * @var Json
     */
    private $json;

    public function __construct(
        Json $json,
        Template\Context $context,
        array $data = []
    ) {
        $this->json = $json;
        
        parent::__construct($context, $data);
    }
    
    /**
     * @inheritDoc
     */
    public function _toHtml()
    {
        if (!$this->isValid()) {
            return '';
        }
        return parent::_toHtml();
    }

    /**
     * @inheritDoc
     */
    public function getCacheKeyInfo()
    {
        return [
            'SYNERISE_RECOMMENDATIONS_DYNAMIC_WIDGET',
            $this->json->serialize($this->getParams()),
            $this->_design->getDesignTheme()->getId(),
            $this->_storeManager->getStore()->getId()
        ];
    }

    /**
     * Build JavaScript configuration object
     */
    public function getJsConfig(): string
    {
        $config = [
            'url' => $this->getAjaxUrl(),
            'params' => $this->getParams()
        ];

        return $this->json->serialize($config);
    }

    /**
     * Get params to be sent with an ajax request
     *
     * @return array
     */
    protected function getParams(): array
    {
        $params = [
            'campaign_id' => $this->getCampaignId()
        ];

        if (!empty($this->getTitle())) {
            $params['title'] = $this->getTitle();
        }

        if (!empty($this->getLayoutHandle())) {
            $params['layout_handle'] = $this->getLayoutHandle();
        }

        return $params;
    }

    /**
     * Check whether the block can be displayed
     *
     * @return bool
     */
    protected function isValid(): bool
    {
        return !empty($this->getCampaignId());
    }
}