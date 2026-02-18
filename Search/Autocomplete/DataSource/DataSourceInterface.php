<?php

namespace Synerise\Integration\Search\Autocomplete\DataSource;

interface DataSourceInterface
{
    public function get(): ?DataInterface;
}