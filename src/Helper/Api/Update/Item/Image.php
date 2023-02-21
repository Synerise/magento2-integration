<?php

namespace Synerise\Integration\Helper\Api\Update\Item;

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
     * @param string $filePath
     * @return string|null
     */
    public function getOriginalImageUrl(string $filePath): ?string
    {
        return $filePath ? $this->assetContext->getBaseUrl() . $filePath : null;
    }
}