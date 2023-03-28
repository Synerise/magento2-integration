<?php

namespace Synerise\Integration\Helper\Api\Event;

use Exception;
use Magento\Catalog\Model\Product;
use Synerise\ApiClient\Model\CustomeventRequest;
use Synerise\ApiClient\Model\EventClientAction;
use Synerise\Integration\Helper\Api\Context;
use Synerise\Integration\Helper\Api\Update\Item\Image;

class Favorites extends AbstractEvent
{
    /**
     * @var Image
     */
    private $imageHelper;

    public function __construct(
        Context $contextHelper,
        Image $imageHelper
    ) {
        $this->imageHelper = $imageHelper;

        parent::__construct($contextHelper);
    }

    /**
     * @param $event
     * @param Product $product
     * @param $uuid
     * @return EventClientAction
     * @throws Exception
     */
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

    /**
     * @param $event
     * @param Product $product
     * @param $uuid
     * @return CustomeventRequest
     * @throws Exception
     */
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

    /**
     * @param Product $product
     * @return array
     */
    public function prepareParams(Product $product): array
    {
        $params = [
            "sku" => $product->getSku(),
            "name" => $product->getName(),
            "productUrl" => $product->getUrlInStore(),
        ];

        if ($product->getImage()) {
            $params['image'] = $this->imageHelper->getOriginalImageUrl($product->getImage());
        }

        return $params;
    }
}