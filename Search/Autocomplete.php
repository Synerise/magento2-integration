<?php

namespace Synerise\Integration\Search;

use Magento\Search\Model\Autocomplete\DataProviderInterface;
use Magento\Search\Model\AutocompleteInterface;

class Autocomplete implements AutocompleteInterface
{
    /**
     * @var DataProviderInterface[]
     */
    private $dataProviders;

    /**
     * @param array $dataProviders
     */
    public function __construct(
        array $dataProviders
    ) {
        $this->dataProviders = $dataProviders;
        ksort($this->dataProviders);
    }

    /**
     * @inheritdoc
     */
    public function getItems()
    {
        $data = [];
        foreach ($this->dataProviders as $dataProvider) {
            $data[] = $dataProvider->getItems();
        }

        return array_merge([], ...$data);
    }
}