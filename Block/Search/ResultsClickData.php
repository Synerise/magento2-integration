<?php

namespace Synerise\Integration\Block\Search;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Block\Product\AwareInterface as ProductAwareInterface;
use Magento\Framework\Search\EngineResolverInterface;
use Magento\Framework\View\Element\Template\Context;
use Synerise\Integration\Search\Container\SearchResponse;

class ResultsClickData extends EngineResolver implements ProductAwareInterface
{
    /**
     * @var ProductInterface
     */
    private $product;

    /**
     * @var SearchResponse
     */
    private $searchResponse;

    public function __construct(
        SearchResponse $searchResponse,
        Context $context,
        EngineResolverInterface $engineResolver,
        array $data = []
    ) {
        $this->searchResponse = $searchResponse;
        parent::__construct($context, $engineResolver, $data);
    }

    public function setProduct(ProductInterface $product)
    {
        $this->product = $product;
        return $this;
    }

    public function getProduct()
    {
        return $this->product;
    }

    public function getCurrentCorrelationId()
    {
        return $this->searchResponse->getCurrentCorrelationId();
    }
}