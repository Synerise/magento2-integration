<?php

namespace Synerise\Integration\Search\Autocomplete\DataSource;

interface DataInterface
{
    public function getType(): string;

    public function getHeader(): ?string;

    public function getValues(): ?array;

    public function getCorrelationId(): ?string;

    public function getCampaignId(): ?string;
}