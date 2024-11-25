<?php

namespace Synerise\Integration\Block\ElasticsuiteTracker\Variables\Page;

use Magento\Framework\View\Element\Template;

if (!class_exists('Smile\ElasticsuiteTracker\Block\Variables\Page\Search')) {
    class Search extends Template
    { }
} else {
    class Search extends \Smile\ElasticsuiteTracker\Block\Variables\Page\Search
    {
        public const COLLECTION_CLASS = 'Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection';

        public function __construct(
            Template\Context $context,
            \Magento\Framework\Json\Helper\Data $jsonHelper,
            \Smile\ElasticsuiteTracker\Helper\Data $trackerHelper,
            \Magento\Framework\Registry $registry,
            \Magento\Catalog\Model\Layer\Resolver $layerResolver,
            \Magento\CatalogSearch\Helper\Data $catalogSearchData,
            \Smile\ElasticsuiteCore\Api\Search\ContextInterface $searchContext,
            array $data = []
        ) {
            $layer = $layerResolver->get();
            $this->canExecute = $layer && is_a($layer->getProductCollection(), self::COLLECTION_CLASS);

            parent::__construct(
                $context,
                $jsonHelper,
                $trackerHelper,
                $registry,
                $layerResolver,
                $catalogSearchData,
                $searchContext,
                $data
            );
        }

        /**
         * @inheritDoc
         */
        public function getVariables()
        {
            if ($this->canExecute) {
                return parent::getVariables();
            }
            return [];
        }
    }
}