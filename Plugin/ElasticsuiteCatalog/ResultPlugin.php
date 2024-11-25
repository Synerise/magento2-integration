<?php

namespace Synerise\Integration\Plugin\ElasticsuiteCatalog;

use Magento\CatalogSearch\Block\Result;

if (!class_exists('Smile\ElasticsuiteCatalog\Block\Plugin\ResultPlugin')) {
    class ResultPlugin {}
} else {
    class ResultPlugin extends \Smile\ElasticsuiteCatalog\Block\Plugin\ResultPlugin
    {
        /**
         * @inheritDoc
         */
        public function aroundGetNoteMessages(Result $resultBlock, \Closure $proceed)
        {
            $collection = $resultBlock->getListBlock()->getLoadedProductCollection();
            if (is_a($collection, 'Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection')) {
                return parent::aroundGetNoteMessages($resultBlock, $proceed);
            }

            return $proceed();
        }
    }
}


