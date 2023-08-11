<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\ObserverInterface;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\CustomeventRequest;

class WishlistRemoveProduct implements ObserverInterface
{
    const EVENT = 'wishlist_item_delete_after';

    /**
     * @var \Synerise\Integration\Helper\Api
     */
    protected $apiHelper;

    /**
     * @var \Synerise\Integration\Helper\Catalog
     */
    protected $catalogHelper;

    /**
     * @var \Synerise\Integration\Helper\Tracking
     */
    protected $trackingHelper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Wishlist\Model\Wishlist
     */
    protected $wishlist;

    /**
     * @var \Synerise\Integration\Helper\Queue
     */
    protected $queueHelper;

    /**
     * @var \Synerise\Integration\Helper\Event
     */
    protected $eventHelper;

    public function __construct(
        \Magento\Wishlist\Model\Wishlist $wishlist,
        \Psr\Log\LoggerInterface $logger,
        \Synerise\Integration\Helper\Api $apiHelper,
        \Synerise\Integration\Helper\Catalog $catalogHelper,
        \Synerise\Integration\Helper\Tracking $trackingHelper,
        \Synerise\Integration\Helper\Queue $queueHelper,
        \Synerise\Integration\Helper\Event $eventHelper
    ) {
        $this->wishlist = $wishlist;
        $this->logger = $logger;
        $this->apiHelper = $apiHelper;
        $this->catalogHelper = $catalogHelper;
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
            /** @var \Magento\Wishlist\Model\Item $item */
            $item = $observer->getItem();

            $storeId = $item->getStoreId();

            /** @var \Magento\Wishlist\Model\Wishlist $wishlist */
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
            if ($source) {
                $params["source"] = $source;
            }
            $params["applicationName"] = $this->trackingHelper->getApplicationName();
            $params["storeId"] = $this->trackingHelper->getStoreId();
            $params["storeUrl"] = $this->trackingHelper->getStoreBaseUrl();

            $customEventRequest = new CustomeventRequest([
                'time' => $this->trackingHelper->getCurrentTime(),
                'action' => 'product.removeFromFavorites',
                'label' => $this->trackingHelper->getEventLabel(self::EVENT),
                'client' => new \Synerise\ApiClient\Model\Client([
                    'custom_id' => $wishlist->getCustomerId()
                ]),
                'params' => $params
            ]);

            if ($this->queueHelper->isQueueAvailable(self::EVENT, $storeId)) {
                $this->queueHelper->publishEvent(self::EVENT, $customEventRequest, $storeId);
            } else {
                $this->eventHelper->sendEvent(self::EVENT, $customEventRequest, $storeId);
            }
        } catch (ApiException $e) {
        } catch (\Exception $e) {
            $this->logger->error('Synerise Error', ['exception' => $e]);
        }
    }
}
