<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Search\EngineResolverInterface;
use Magento\Framework\UrlInterface;

class AdvancedSearchRedirect implements ObserverInterface
{
    /**
     * @var EngineResolverInterface
     */
    protected $engineResolver;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @param EngineResolverInterface $engineResolver
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        EngineResolverInterface $engineResolver,
        UrlInterface $urlBuilder
    ) {
        $this->engineResolver = $engineResolver;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Override advanced search action if AI search is enabled
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        if ($this->engineResolver->getCurrentSearchEngine() == 'synerise_ai') {
            $observer->getControllerAction()
                ->getResponse()
                ->setRedirect($this->urlBuilder->getUrl('/'));
        }
    }
}