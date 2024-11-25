<?php

namespace Synerise\Integration\Search;

use Magento\Framework\ObjectManager\NoninterceptableInterface;

if (!class_exists('Smile\ElasticsuiteCatalog\Model\Layer\FilterList')) {
    class FilterList extends \Magento\Catalog\Model\Layer\FilterList {}
} else {
    class FilterList extends \Smile\ElasticsuiteCatalog\Model\Layer\FilterList implements NoninterceptableInterface {}
}
