<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Wishlist\Model\Wishlist;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CustomeventRequest;
use Synerise\Integration\Helper\Category;
use Synerise\Integration\MessageQueue\Sender\Event;
use Synerise\Integration\Helper\Image;
use Synerise\Integration\MessageQueue\Publisher\Event as Publisher;
use Synerise\Integration\Helper\Tracking;

class WishlistRemoveProduct implements ObserverInterface
{
    const EVENT = 'wishlist_item_delete_after';

    /**
     * @var Wishlist
     */
    protected $wishlist;

    /**
     * @var Tracking
     */
    protected $trackingHelper;

    /**
     * @var Category
     */
    protected $categoryHelper;

    /**
     * @var Image
     */
    protected $imageHelper;

    /**
     * @var Publisher
     */
    protected $publisher;

    /**
     * @var Event
     */
    protected $sender;

    public function __construct(
        Wishlist $wishlist,
        Category $categoryHelper,
        Image $imageHelper,
        Tracking $trackingHelper,
        Publisher $publisher,
        Event $sender
    ) {
        $this->wishlist = $wishlist;
        $this->categoryHelper = $categoryHelper;
        $this->imageHelper = $imageHelper;
        $this->trackingHelper = $trackingHelper;
        $this->publisher = $publisher;
        $this->sender = $sender;
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
            /** @var \Magento\Wishlist\Model\Item $item */
            $item = $observer->getItem();

            $storeId = $item->getStoreId();

            /** @var Wishlist $wishlist */
            $wishlist = $this->wishlist->load($item->getWishlistId());

            if (!$wishlist->getCustomerId()) {
                return;
            }

            /** @var \Magento\Catalog\Model\Product $product */
            $product = $item->getProduct();

            $params = [
                "sku" => $product->getSku(),
                "name" => $product->getName(),
                "productUrl" => $product->getUrlInStore(),
            ];

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

            $source = $this->trackingHelper->getSource();
            if ($source) {
                $params["source"] = $source;
            }
            $params["applicationName"] = $this->trackingHelper->getApplicationName();
            $params["storeId"] = $this->trackingHelper->getStoreId();
            $params["storeUrl"] = $this->trackingHelper->getStoreBaseUrl();

            $customEventRequest = new CustomeventRequest([
                'event_salt' => $this->trackingHelper->generateEventSalt(),
                'time' => $this->trackingHelper->getCurrentTime(),
                'action' => 'product.removeFromFavorites',
                'label' => $this->trackingHelper->getEventLabel(self::EVENT),
                'client' => new \Synerise\ApiClient\Model\Client([
                    'custom_id' => $wishlist->getCustomerId()
                ]),
                'params' => $params
            ]);

            if ($this->trackingHelper->isQueueAvailable(self::EVENT, $storeId)) {
                $this->publisher->publish(self::EVENT, $customEventRequest, $storeId);
            } else {
                $this->sender->send(self::EVENT, $customEventRequest, $storeId);
            }
        } catch (ApiException $e) {
        } catch (\Exception $e) {
            $this->trackingHelper->getLogger()->error($e);
        }
    }
}
