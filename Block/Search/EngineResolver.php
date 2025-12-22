<?php

namespace Synerise\Integration\Block\Search;

use Magento\Framework\Search\EngineResolverInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class EngineResolver extends Template
{
    /**
     * @var EngineResolverInterface
     */
    protected $engineResolver;

    public function __construct(
        Context $context,
        EngineResolverInterface $engineResolver,
        array $data = []
    ) {
        $this->engineResolver = $engineResolver;
        parent::__construct($context, $data);
    }

    public function isSyneriseAiEnabled(): bool
    {
        return $this->engineResolver->getCurrentSearchEngine() == 'synerise_ai';
    }
}