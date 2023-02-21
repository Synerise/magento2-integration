<?php

namespace Synerise\Integration\Helper\Api\Update\Item;

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

    public function __construct(
        CategoryRepository $categoryRepository
    ) {
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @param $categoryId
     * @return string|null
     * @throws NoSuchEntityException
     */
    public function getFormattedCategoryPath($categoryId): ?string
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
}