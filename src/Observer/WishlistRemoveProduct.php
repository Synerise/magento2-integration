<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\ObserverInterface;
use Synerise\ApiClient\Model\CustomeventRequest;

class WishlistRemoveProduct implements ObserverInterface
{
    const EVENT = 'wishlist_item_delete_after';

    protected $apiHelper;
    protected $trackingHelper;
    protected $logger;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Synerise\Integration\Helper\Api $apiHelper,
        \Synerise\Integration\Helper\Catalog $catalogHelper,
        \Synerise\Integration\Helper\Tracking $trackingHelper
    ) {
        $this->logger = $logger;
        $this->apiHelper = $apiHelper;
        $this->catalogHelper = $catalogHelper;
        $this->trackingHelper = $trackingHelper;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->trackingHelper->isLiveEventTrackingEnabled(self::EVENT)) {
            return;
        }

        if ($this->trackingHelper->isAdminStore()) {
            return;
        }

        try {

            /** @var \Magento\Wishlist\Model\Item $wishlistItem */
            $wishlistItem = $observer->getEvent()->getItem();

            $product = $wishlistItem->getProduct();

            $params = [
                "sku" => $product->getSku(),
                "name" => $product->getName(),
                "productUrl" => $product->getUrlInStore(),
            ];

            $categoryIds = $product->getCategoryIds();
            if ($categoryIds) {
                $params['categories'] = [];
                foreach ($categoryIds as $categoryId) {
                    $params['categories'][] = $this->catalogHelper->getFormattedCategoryPath($categoryId);
                }

                if ($product->getCategoryId()) {
                    $category = $this->catalogHelper->getFormattedCategoryPath($product->getCategoryId());
                    if ($category) {
                        $params['category'] = $category;
                    }
                }
            }

            if ($product->getImage()) {
                $params['image'] = $this->catalogHelper->getOriginalImageUrl($product->getImage());
            }

            $source = $this->trackingHelper->getSource();
            if($source) {
                $params["source"] = $source;
            }
            $params["applicationName"] = $this->trackingHelper->getApplicationName();

            $customEventRequest = new CustomeventRequest([
                'time' => $this->trackingHelper->getCurrentTime(),
                'action' => 'product.removeFromFavorites',
                'label' => $this->trackingHelper->getEventLabel(self::EVENT),
                'client' => [
                    'uuid' => $this->trackingHelper->getClientUuid()
                ],
                'params' => $params
            ]);

            $this->apiHelper->getDefaultApiInstance()
                ->customEvent('4.4', $customEventRequest);

        } catch (\Exception $e) {
            $this->logger->error('Synerise Api request failed', ['exception' => $e]);
        }
    }
}
