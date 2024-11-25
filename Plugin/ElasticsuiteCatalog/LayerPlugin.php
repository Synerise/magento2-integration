<?php

namespace Synerise\Integration\Plugin\ElasticsuiteCatalog;

if (!class_exists('Smile\ElasticsuiteCatalog\Plugin\LayerPlugin')) {
    class LayerPlugin {}
} else {
    class LayerPlugin extends \Smile\ElasticsuiteCatalog\Plugin\LayerPlugin
    {
        public function beforePrepareProductCollection(
            \Magento\Catalog\Model\Layer $layer,
            \Magento\Catalog\Model\ResourceModel\Collection\AbstractCollection $collection
        ) {
            if (is_a($collection, 'Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection')) {
                parent::beforePrepareProductCollection($layer, $collection);
            }
        }
    }
}
