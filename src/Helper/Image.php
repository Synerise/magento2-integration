<?php

namespace Synerise\Integration\Helper;

use Magento\Framework\View\Asset\ContextInterface;

class Image
{
    /**
     * @var ContextInterface
     */
    private $assetContext;

    public function __construct(
        ContextInterface $assetContext
    ) {
        $this->assetContext = $assetContext;
    }

    /**
     * Get URL to the original version of the product image.
     *
     * @return string|null
     */
    public function getOriginalImageUrl($filePath)
    {
        return $filePath ? $this->assetContext->getBaseUrl() . $filePath : null;
    }
}