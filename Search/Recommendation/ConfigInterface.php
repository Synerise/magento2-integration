<?php

namespace Synerise\Integration\Search\Recommendation;

interface ConfigInterface
{
    public function isEnabled(): bool;

    public function getCampaignId(): ?string;

    public function getHeader(): ?string;
}