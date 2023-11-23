<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\ObserverInterface;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\EventClientAction;
use Synerise\Integration\Helper\Category;
use Synerise\Integration\Helper\Event;
use Synerise\Integration\Helper\Image;
use Synerise\Integration\Helper\Queue;
use Synerise\Integration\Helper\Tracking;

class WishlistAddProduct implements ObserverInterface
{
    const EVENT = 'wishlist_add_product';

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
     * @var Queue
     */
    protected $queueHelper;

    /**
     * @var Event
     */
    protected $eventHelper;

    public function __construct(
        Category $categoryHelper,
        Image $imageHelper,
        Tracking $trackingHelper,
        Queue $queueHelper,
        Event $eventHelper
    ) {
        $this->categoryHelper = $categoryHelper;
        $this->imageHelper = $imageHelper;
        $this->trackingHelper = $trackingHelper;
        $this->queueHelper = $queueHelper;
        $this->eventHelper = $eventHelper;
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
            /** @var \Magento\Wishlist\Model\Wishlist $wishlist */
            $wishlist = $observer->getEvent()->getWishlist();
            if (!$wishlist->getCustomerId()) {
                return;
            }

            $product = $observer->getEvent()->getProduct();
            $storeId = $this->trackingHelper->getStoreId();
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

            $eventClientAction = new EventClientAction([
                'event_salt' => $this->trackingHelper->generateEventSalt(),
                'time' => $this->trackingHelper->getCurrentTime(),
                'label' => $this->trackingHelper->getEventLabel(self::EVENT),
                'client' => new \Synerise\ApiClient\Model\Client([
                    'custom_id' => $wishlist->getCustomerId()
                ]),
                'params' => $params
            ]);

            if ($this->queueHelper->isQueueAvailable(self::EVENT, $storeId)) {
                $this->queueHelper->publishEvent(self::EVENT, $eventClientAction, $storeId);
            } else {
                $this->eventHelper->sendEvent(self::EVENT, $eventClientAction, $storeId);
            }
        } catch (ApiException $e) {
        } catch (\Exception $e) {
            $this->trackingHelper->getLogger()->error($e);
        }
    }
}
