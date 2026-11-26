<?php

namespace Synerise\Integration\Search;

use Magento\Framework\App\RequestInterface;
use Magento\Search\Model\AutocompleteInterface;
use Synerise\Integration\Search\Autocomplete\DataProviderResolver;

class Autocomplete implements AutocompleteInterface
{
    /**
     * @var DataProviderResolver
     */
    private $resolver;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @param DataProviderResolver $resolver
     * @param RequestInterface $request
     */
    public function __construct(
        DataProviderResolver $resolver,
        RequestInterface $request
    ) {
        $this->resolver = $resolver;
        $this->request = $request;
    }

    /**
     * @inheritdoc
     */
    public function getItems()
    {
        $data = [];

        $dataProviders = $this->resolver->resolve($this->request->getParam('q') !== null ? 'default' : 'zero_state');
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