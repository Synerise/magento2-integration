<?php

namespace Synerise\Integration\Helper\Event;

use Magento\Catalog\Model\Product;
use Synerise\ApiClient\Model\CustomeventRequest;
use Synerise\ApiClient\Model\EventClientAction;
use Synerise\Integration\Helper\Api;
use Synerise\Integration\Helper\Data\Context as ContextHelper;
use Synerise\Integration\Helper\Data\Product as ProductHelper;

class Favorites extends AbstractEvent
{
    /**
     * @var ProductHelper
     */
    private $productHelper;

    public function __construct(
        Api $apiHelper,
        ContextHelper $contextHelper,
        ProductHelper $productHelper
    ) {
        $this->productHelper = $productHelper;

        parent::__construct($apiHelper, $contextHelper);
    }

    public function sendClientAddedProductToFavoritesEvent(EventClientAction $request) {
        return $this->apiHelper->getDefaultApiInstance()
            ->clientAddedProductToFavoritesWithHttpInfo('4.4', $request);
    }

    public function prepareClientAddedProductToFavoritesRequest($event, Product $product, $uuid): EventClientAction {
        return new EventClientAction(
            $this->prepareEventData(
                $this->getEventLabel($event),
                new \Synerise\ApiClient\Model\Client([
                    'uuid' => $uuid,
                ]),
                $this->prepareParams($product)
            )
        );
    }

    public function prepareClientRemovedProductFromFavoritesRequest($event, Product $product, $uuid): CustomeventRequest {
        return new CustomeventRequest(
            $this->prepareEventData(
                $this->getEventLabel($event),
                new \Synerise\ApiClient\Model\Client([
                    'uuid' => $uuid,
                ]),
                $this->prepareParams($product),
                'product.removeFromFavorites'
            )
        );
    }

    public function prepareParams(Product $product) {

        $params = [
            "sku" => $product->getSku(),
            "name" => $product->getName(),
            "productUrl" => $product->getUrlInStore(),
        ];

        $categoryIds = $product->getCategoryIds();
        if ($categoryIds) {
            $params['categories'] = [];
            foreach ($categoryIds as $categoryId) {
                $params['categories'][] = $this->productHelper->getFormattedCategoryPath($categoryId);
            }

            if ($product->getCategoryId()) {
                $category = $this->productHelper->getFormattedCategoryPath($product->getCategoryId());
                if ($category) {
                    $params['category'] = $category;
                }
            }
        }

        if ($product->getImage()) {
            $params['image'] = $this->productHelper->getOriginalImageUrl($product->getImage());
        }

        return $params;
    }
}