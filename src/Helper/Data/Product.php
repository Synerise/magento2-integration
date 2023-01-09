<?php

namespace Synerise\Integration\Helper\Data;

use Magento\Catalog\Model\CategoryRepository;
use Magento\Framework\View\Asset\ContextInterface;

class Product
{
    /**
     * @var ContextInterface
     */
    private $assetContext;

    /**
     * @var array
     */
    protected $formattedCategoryPaths = [];

    /**
     * @var CategoryRepository
     */
    private $categoryRepository;

    public function __construct(
        ContextInterface $assetContext,
        CategoryRepository $categoryRepository
    ) {
        $this->assetContext = $assetContext;
        $this->categoryRepository = $categoryRepository;
    }

    public function getFormattedCategoryPath($categoryId)
    {
        if (!isset($this->formattedCategoryPaths[$categoryId])) {
            /** @var $category \Magento\Catalog\Model\Category */
            $category = $this->categoryRepository->get($categoryId);

            if ($category->getParentId()) {
                $parentCategoryPath = $this->getFormattedCategoryPath($category->getParentId());
                $this->formattedCategoryPaths[$categoryId] = $parentCategoryPath ?
                    $parentCategoryPath . ' > ' . $category->getName() : $category->getName();
            } else {
                $this->formattedCategoryPaths[$categoryId] = $category->getName();
            }
        }

        return $this->formattedCategoryPaths[$categoryId] ?: null;
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