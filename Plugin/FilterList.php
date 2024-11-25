<?php

namespace Synerise\Integration\Plugin;

class FilterList
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var mixed
     */
    private $filterList = null;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    public function aroundGetFilters($subject, callable $proceed, \Magento\Catalog\Model\Layer $layer)
    {
        if (!is_a($layer->getProductCollection(), 'Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection')) {
            return $proceed($layer);
        }

        if (!$this->filterList) {
            $this->filterList = $this->objectManager->get(\Synerise\Integration\Search\FilterList::class);
        }

        return $this->filterList->getFilters($layer);
    }
}