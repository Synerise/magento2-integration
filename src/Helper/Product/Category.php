<?php

namespace Synerise\Integration\Helper\Product;

use Magento\Catalog\Model\CategoryRepository;
use Magento\Framework\Exception\NoSuchEntityException;

class Category
{
    /**
     * @var array
     */
    protected $formattedCategoryPaths = [];

    /**
     * @var CategoryRepository
     */
    private $categoryRepository;

    /**
     * @param CategoryRepository $categoryRepository
     */
    public function __construct(
        CategoryRepository $categoryRepository
    ) {
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Get current product category in open graph format
     *
     * @param int $categoryId
     * @return string|null
     */
    public function getFormattedCategoryPath(int $categoryId): ?string
    {
        if (!isset($this->formattedCategoryPaths[$categoryId])) {
            try {
                /** @var $category \Magento\Catalog\Model\Category */
                $category = $this->categoryRepository->get($categoryId);

                if ($category->getParentId()) {
                    $parentCategoryPath = $this->getFormattedCategoryPath($category->getParentId());
                    $this->formattedCategoryPaths[$categoryId] = $parentCategoryPath ?
                        $parentCategoryPath . ' > ' . $category->getName() : $category->getName();
                } else {
                    $this->formattedCategoryPaths[$categoryId] = $category->getName();
                }
            } catch (NoSuchEntityException $e) {
                return null;
            }
        }

        return $this->formattedCategoryPaths[$categoryId] ?: null;
    }
}
