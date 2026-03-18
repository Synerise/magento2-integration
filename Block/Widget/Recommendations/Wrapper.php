<?php

namespace Synerise\Integration\Block\Widget\Recommendations;

use Magento\Customer\Model\Context;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Template;
use Synerise\Integration\ViewModel\Recommendations\DataFactory;

class Wrapper extends Template
{
    /**
     * @var DataFactory
     */
    protected $dataFactory;

    /**
     * @var HttpContext
     */
    protected $httpContext;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var Json
     */
    protected $json;

    public function __construct(
        DataFactory $dataFactory,
        HttpContext $httpContext,
        PriceCurrencyInterface $priceCurrency,
        Json $json,
        Template\Context $context,
        array $data = []
    ) {
        $this->dataFactory = $dataFactory;
        $this->httpContext = $httpContext;
        $this->priceCurrency = $priceCurrency;
        $this->json = $json;

        parent::__construct($context, $data);
    }

    /**
     * @inheritDoc
     */
    protected function _loadCache()
    {
        $data = $this->getData();
        unset($data['type']);
        unset($data['module_name']);
        unset($data['cache_lifetime']);
        unset($data['cache_tags']);

        $this->setCacheKeyInfo([
            'SYNERISE_RECOMMENDATIONS_STATIC_WIDGET',
            $this->json->serialize($data),
            $this->priceCurrency->getCurrency()->getCode(),
            $this->_design->getDesignTheme()->getId(),
            $this->httpContext->getValue(Context::CONTEXT_GROUP),
            $this->json->serialize($this->httpContext->getValue('tax_rates')),
            $this->_storeManager->getStore()->getId()
        ]);

        return parent::_loadCache();
    }

    /**
     * @inheritDoc
     */
    public function getCacheKeyInfo()
    {
        return $this->getData('cache_key_info');
    }

    /**
     * @inheritDoc
     */
    protected function _beforeToHtml()
    {
        parent::_beforeToHtml();

        $this->initRecommendationsData();
        $this->initBlockData();

        foreach ($this->getChildBlocks() as $childBlock) {
            $this->propagateDataToBlock($childBlock);
        }

        return $this;
    }

    /**
     * Fetch recommendations from view model
     *
     * @return void
     */
    protected function initRecommendationsData()
    {
        $data = $this->dataFactory->create($this->getData());

        $this->setProductCollection($data->getProductCollection());
        $this->setCorrelationId($data->getCorrelationId());
    }

    /**
     * Init data to be set for block
     */
    protected function initBlockData()
    {
        $data = $this->getData();

        unset($data['type']);
        unset($data['module_name']);

        $data['cache_lifetime'] = false;

        $this->setBlockData($data);
    }

    /**
     * Get all child blocks
     *
     * @return array
     * @throws LocalizedException
     */
    protected function getChildBlocks()
    {
        return $this->getLayout()->getChildBlocks($this->getNameInLayout());
    }

    /**
     * Set data on a child block
     *
     * @param Template $block
     * @return void
     */
    protected function propagateDataToBlock(Template $block)
    {
        $block->addData($this->getBlockData());
    }
}