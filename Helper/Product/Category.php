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

    /**
     * Get full list of category ids including indirect associations
     *
     * @param int[] $categoryIds
     * @return int[]
     */
    public function getAllCategoryIds(array $categoryIds): array
    {
        $allCategoryIds = [];
        foreach ($categoryIds as $categoryId) {
            if (!in_array($categoryId, $allCategoryIds)) {
                $allCategoryIds[] = (int) $categoryId;
                $currentId = $categoryId;
                while($currentId = $this->getParentCategoryId($currentId)) {
                    if (!in_array($currentId, $allCategoryIds)) {
                        $allCategoryIds[] = (int) $currentId;
                    }
                }
            }
        }
        return $allCategoryIds;
    }

    /**
     * Get parent category ID
     *
     * @param int $categoryId
     * @return int|null
     */
    protected function getParentCategoryId(int $categoryId): ?int
    {
        try {
            /** @var $category \Magento\Catalog\Model\Category */
            $category = $this->categoryRepository->get($categoryId);
            return $category->getParentId();
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }
}
