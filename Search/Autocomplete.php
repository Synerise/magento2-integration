<?php

namespace Synerise\Integration\Search;

use Magento\Search\Model\AutocompleteInterface;
use Synerise\Integration\Search\Autocomplete\DataProviderResolver;

class Autocomplete implements AutocompleteInterface
{
    /**
     * @var DataProviderResolver
     */
    private $resolver;

    /**
     * @param DataProviderResolver $resolver
     */
    public function __construct(
        DataProviderResolver $resolver
    ) {
        $this->resolver = $resolver;
    }

    /**
     * @inheritdoc
     */
    public function getItems()
    {
        $data = [];

        $dataProviders = $this->resolver->resolve($_GET['q'] == '#' ? 'zero_state' : 'default');
        foreach ($dataProviders as $dataProvider) {
            if ($items = $dataProvider->getItems()) {
                $data[] = $items;
            }
        }

        if (empty($data)) {
            $dataProviders = $this->resolver->resolve('no_results');
            foreach ($dataProviders as $dataProvider) {
                $data[] = $dataProvider->getItems();
            }
        }

        return array_merge([], ...$data);
    }
}