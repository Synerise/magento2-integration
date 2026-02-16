<?php

namespace Synerise\Integration\Block\Tracking\Event;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Template;

class RecommendationView extends Template
{
    /**
     * @param Json $json
     */
    protected $json;

    public function __construct(Json $json, Template\Context $context, array $data = [])
    {
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
     * Get data to be sent with event as json
     *
     * @return bool|string
     */
    public function getEventDataJSON()
    {
        return $this->json->serialize($this->getEventData());
    }

    /**
     * Get data to be sent with event
     *
     * @return array
     */
    public function getEventData()
    {
        if (!$this->getCampaignId()) {
            return [];
        }

        $collection = $this->getProductCollection();

        return [
            'correlationId' => $this->getCorrelationId(),
            'campaignId' => $this->getCampaignId(),
            'items' => $collection ? $collection->getColumnValues('sku') : []
        ];
    }

    /**
     * Check whether the block can be displayed
     *
     * @return bool
     */
    protected function isValid()
    {
        return !empty($this->getEventData());
    }
}