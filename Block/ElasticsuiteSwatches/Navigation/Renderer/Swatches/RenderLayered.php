<?php

namespace Synerise\Integration\Block\ElasticsuiteSwatches\Navigation\Renderer\Swatches;

use Magento\Catalog\Model\Layer\Resolver;
use Magento\Catalog\Model\ResourceModel\Layer\Filter\AttributeFactory;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Eav\Model\Entity\Attribute\Option;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Swatches\Helper\Data;
use Magento\Swatches\Helper\Media;
use Magento\Theme\Block\Html\Pager;

if (!class_exists('Smile\ElasticsuiteSwatches\Block\Navigation\Renderer\Swatches\RenderLayered')) {
    class RenderLayered extends Template {}
} else {
    class RenderLayered extends \Smile\ElasticsuiteSwatches\Block\Navigation\Renderer\Swatches\RenderLayered
    {
        public const COLLECTION_CLASS = 'Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection';

        public function __construct(
            Context $context,
            Attribute $eavAttribute,
            AttributeFactory $layerAttribute,
            Data $swatchHelper,
            Media $mediaHelper,
            Resolver $layerResolver,
            array $data = [],
            ?Pager $htmlPagerBlock = null
        ) {
            $layer = $layerResolver->get();
            $this->canExecute = $layer && is_a($layer->getProductCollection(), self::COLLECTION_CLASS);

            parent::__construct($context, $eavAttribute, $layerAttribute, $swatchHelper, $mediaHelper, $data, $htmlPagerBlock);
        }

        /**
         * {@inheritDoc}
         */
        protected function getFilterOption(array $filterItems, Option $swatchOption)
        {
            if ($this->canExecute) {
                return parent::getFilterOption($filterItems, $swatchOption);
            }

            $filterItem = $this->getFilterItemById($filterItems, $swatchOption->getValue());
            if ($filterItem && $this->isOptionVisible($filterItem)) {
                return $this->getOptionViewData($filterItem, $swatchOption);
            }

            return false;
        }
    }
}