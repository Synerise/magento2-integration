<?php

namespace Synerise\Integration\Search\Autocomplete\DataSource;

interface DataInterface
{
    public function getHeader(): ?string;

    public function getValues(): ?array;

    public function getCorrelationId(): ?string;
}