<?php

namespace Synerise\Integration\Search\Recommendation\ZeroStateRecent;

use Synerise\Integration\Search\Recommendation\Config\Data;
use Synerise\Integration\Search\Recommendation\Config\DataFactory;
use Synerise\Integration\Search\Recommendation\ConfigInterface;

class Config implements ConfigInterface
{
    /**
     * @var Data
     */
    private $dataStorage;

    public function __construct(
        DataFactory $dataFactory,
        int $storeId
    ) {
        $this->dataStorage = $dataFactory->create($storeId);
    }

    public function isEnabled(): bool
    {
        return $this->dataStorage->get('zeroStateRecent/enabled', false);
    }

    public function getCampaignId(): ?string
    {
        return null;
    }

    public function getHeader(): ?string
    {
        return $this->dataStorage->get('zeroStateRecent/header');
    }
}