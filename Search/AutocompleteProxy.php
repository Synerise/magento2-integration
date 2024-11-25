<?php

namespace Synerise\Integration\Search;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Search\EngineResolverInterface;
use Magento\Search\Model\AutocompleteInterface;

class AutocompleteProxy implements AutocompleteInterface
{
    /**
     * @var AutocompleteInterface
     */
    protected $proxy;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param EngineResolverInterface $engineResolver
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        EngineResolverInterface $engineResolver
    ) {
        if ($engineResolver->getCurrentSearchEngine() == 'synerise_ai') {
            $this->proxy = $objectManager->get('Synerise\Integration\Search\Autocomplete');
        } else {
            $this->proxy = $objectManager->get('Magento\Search\Model\Autocomplete');
        }
    }

    /**
     * @inheritDoc
     */
    public function getItems()
    {
        return $this->proxy->getItems();
    }
}