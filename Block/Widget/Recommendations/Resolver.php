<?php

namespace Synerise\Integration\Block\Widget\Recommendations;

use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutFactory;
use Magento\Widget\Block\BlockInterface;

class Resolver extends Template implements BlockInterface
{
    public const DEFAULT_LAYOUT_HANDLE = 'synerise_product_recommendations';

    /**
     * @var LayoutFactory
     */
    protected $layoutFactory;


    public function __construct(
        LayoutFactory $layoutFactory,
        Template\Context $context,
        array $data = []
    ) {
        $this->layoutFactory = $layoutFactory;

        parent::__construct($context, $data);

        if (!$this->getRequest()->isXmlHttpRequest()) {
            $this->addData([
                'cache_lifetime' => 86400,
                'cache_tags' => [
                    Product::CACHE_TAG,
                ],
            ]);
        }
    }

    /**
     * @inheritDoc
     */
    public function toHtml()
    {
        $block = $this->resolveBlock();

        if (!$block) {
            throw new \Exception('Recommendation block not found');
        }

        $block->addData($this->getBlockData());

        return $block->toHtml();
    }

    /**
     * Resolve block for proper rendering
     *
     * @return bool|\Magento\Framework\View\Element\BlockInterface
     * @throws LocalizedException
     */
    protected function resolveBlock()
    {
        $layout = $this->layoutFactory->create(['cacheable' => false]);
        $layoutHandle = $this->getLayoutHandle() ?: self::DEFAULT_LAYOUT_HANDLE;

        $layout->getUpdate()->addHandle($layoutHandle)->load();
        $layout->generateXml()->generateElements();

        if ($this->isDynamic()) {
            return $layout->getBlock('synerise.recommendations.dynamic');
        }

        return $layout->getBlock('synerise.recommendations.wrapper');
    }

    /**
     * Get data set for block
     *
     * @return array
     */
    protected function getBlockData(): array
    {
        $data = $this->getData();

        unset($data['type']);
        unset($data['module_name']);

        return $data;
    }

    /**
     * Check whether the widget should be rendered dynamcally
     * @return bool
     */
    public function isDynamic(): bool
    {
        return !$this->getStatic() && !$this->getRequest()->isXmlHttpRequest();
    }
}