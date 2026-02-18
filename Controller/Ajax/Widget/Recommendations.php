<?php

namespace Synerise\Integration\Controller\Ajax\Widget;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\LayoutFactory;
use Magento\Store\Model\StoreManagerInterface;
use Synerise\Integration\Block\Widget\Recommendations\Resolver;

class Recommendations implements HttpGetActionInterface
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var RawFactory
     */
    protected $resultRawFactory;

    /**
     * @var LayoutFactory
     */
    protected $layoutFactory;

    public function __construct(
        RequestInterface $request,
        StoreManagerInterface $storeManager,
        RawFactory $resultRawFactory,
        LayoutFactory $layoutFactory
    ) {
        $this->request = $request;
        $this->storeManager = $storeManager;
        $this->resultRawFactory = $resultRawFactory;
        $this->layoutFactory = $layoutFactory;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $layout = $this->layoutFactory->create();

        $block = $layout->createBlock(
            Resolver::class,
            'recommendation.widget',
            ['data' => $this->getBlockData()]
        );

        return $this->resultRawFactory->create()->setContents($block->toHtml());
    }

    /**
     * Prepare an array of data to be passed to a wrapper block.
     *
     * @return array
     * @throws NoSuchEntityException
     */
    protected function getBlockData()
    {
        return [
            'campaign_id' => $this->request->getParam('campaign_id'),
            'title' => $this->request->getParam('title'),
            'store_id' => $this->storeManager->getStore()->getId(),
            'layout_handle' => $this->request->getParam('layout_handle'),
            'cache_lifetime' => false
        ];
    }
}