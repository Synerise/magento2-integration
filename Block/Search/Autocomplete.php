<?php

namespace Synerise\Integration\Block\Search;

use Magento\Customer\CustomerData\JsLayoutDataProviderPoolInterface;
use Magento\Framework\Search\EngineResolverInterface;
use Magento\Framework\View\Element\Template\Context;

class Autocomplete extends EngineResolver
{
    /**
     * @var EngineResolverInterface
     */
    protected $engineResolver;

    public function __construct(
        Context $context,
        JsLayoutDataProviderPoolInterface $jsLayoutDataProvider,
        EngineResolverInterface $engineResolver,
        array $data = []
    ) {
        if (isset($data['jsLayout'])) {
            $this->jsLayout = array_merge_recursive($jsLayoutDataProvider->getData(), $data['jsLayout']);
            unset($data['jsLayout']);
        } else {
            $this->jsLayout = $jsLayoutDataProvider->getData();
        }

        parent::__construct($context, $engineResolver, $data);
    }
}