<?php

namespace Synerise\Integration\SyneriseApi\Mapper;

use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Wishlist\Model\Wishlist;
use Synerise\ApiClient\Model\Client;
use Synerise\ApiClient\Model\EventClientAction;
use Synerise\Integration\Helper\Product\Category;
use Synerise\Integration\Helper\Product\Image;
use Synerise\Integration\Helper\Tracking\Context;

class WishlistAddProduct
{
    /**
     * @var Category
     */
    protected $categoryHelper;

    /**
     * @var Context
     */
    protected $contextHelper;

    /**
     * @var Image
     */
    protected $imageHelper;

    /**
     * @param Category $categoryHelper
     * @param Context $contextHelper
     * @param Image $imageHelper
     */
    public function __construct(
        Category $categoryHelper,
        Context $contextHelper,
        Image $imageHelper
    ) {
        $this->categoryHelper = $categoryHelper;
        $this->contextHelper = $contextHelper;
        $this->imageHelper = $imageHelper;
    }

    /**
     * Prepare wishlist add event client action request
     *
     * @param string $event
     * @param Wishlist $wishlist
     * @param Product $product
     * @return EventClientAction
     * @throws LocalizedException
     * @throws \Exception
     */
    public function prepareRequest(string $event, Wishlist $wishlist, Product $product): EventClientAction
    {
        $params = $this->contextHelper->prepareContextParams();
        $params['sku'] = $product->getSku();
        $params['name'] = $product->getName();
        $params['productUrl'] = $product->getUrlInStore();

        $categoryIds = $product->getCategoryIds();
        if ($categoryIds) {
            $params['categories'] = [];
            foreach ($categoryIds as $categoryId) {
                $params['categories'][] = $this->categoryHelper->getFormattedCategoryPath($categoryId);
            }

            if ($product->getCategoryId()) {
                $category = $this->categoryHelper->getFormattedCategoryPath($product->getCategoryId());
                if ($category) {
                    $params['category'] = $category;
                }
            }
        }

        if ($product->getImage()) {
            $params['image'] = $this->imageHelper->getOriginalImageUrl($product->getImage());
        }

        return new EventClientAction([
            'event_salt' => $this->contextHelper->generateEventSalt(),
            'time' => $this->contextHelper->getCurrentTime(),
            'label' => $this->contextHelper->getEventLabel($event),
            'client' => new Client([
                'custom_id' => $wishlist->getCustomerId()
            ]),
            'params' => $params
        ]);
    }
}