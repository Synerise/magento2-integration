<?php

namespace Synerise\Integration\Search\Autocomplete\DataSource;

use Magento\Framework\DataObject;

class Data extends DataObject implements DataInterface
{
    public function getType(): string
    {
        return $this->_getData('type') ?: 'query';
    }

    public function getHeader(): ?string
    {
        return $this->_getData('header');
    }

    public function getValues(): ?array
    {
        return $this->_getData('values');
    }

    public function getCorrelationId(): ?string
    {
        return $this->_getData('correlation_id');
    }

    public function getCampaignId(): ?string
    {
        return $this->_getData('campaign_id');
    }
}