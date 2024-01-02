<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Synerise\ApiClient\ApiException;
use Synerise\ApiClient\Model\EventClientAction;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Helper\Product\Category;
use Synerise\Integration\SyneriseApi\Sender\Event;
use Synerise\Integration\Helper\Product\Image;
use Synerise\Integration\MessageQueue\Publisher\Event as Publisher;
use Synerise\Integration\Helper\Tracking;

class WishlistAddProduct implements ObserverInterface
{
    public const EVENT = 'wishlist_add_product';

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
     * @var Logger
     */
    protected $loggerHelper;

    /**
     * @var Publisher
     */
    protected $publisher;

    /**
     * @var Event
     */
    protected $sender;

    /**
     * @param Category $categoryHelper
     * @param Image $imageHelper
     * @param Logger $loggerHelper
     * @param Tracking $trackingHelper
     * @param Publisher $publisher
     * @param Event $sender
     */
    public function __construct(
        Category $categoryHelper,
        Image $imageHelper,
        Logger $loggerHelper,
        Tracking $trackingHelper,
        Publisher $publisher,
        Event $sender
    ) {
        $this->categoryHelper = $categoryHelper;
        $this->imageHelper = $imageHelper;
        $this->loggerHelper = $loggerHelper;
        $this->trackingHelper = $trackingHelper;
        $this->publisher = $publisher;
        $this->sender = $sender;
    }

    /**
     * Execute
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if (!$this->trackingHelper->isEventTrackingAvailable(self::EVENT)) {
            return;
        }

        if ($this->trackingHelper->getContext()->isAdminStore()) {
            return;
        }

        try {
            /** @var \Magento\Wishlist\Model\Wishlist $wishlist */
            $wishlist = $observer->getEvent()->getWishlist();
            if (!$wishlist->getCustomerId()) {
                return;
            }

            $product = $observer->getEvent()->getProduct();

            $params = $this->trackingHelper->prepareContextParams();
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

            $eventClientAction = new EventClientAction([
                'event_salt' => $this->trackingHelper->generateEventSalt(),
                'time' => $this->trackingHelper->getContext()->getCurrentTime(),
                'label' => $this->trackingHelper->getEventLabel(self::EVENT),
                'client' => new \Synerise\ApiClient\Model\Client([
                    'custom_id' => $wishlist->getCustomerId()
                ]),
                'params' => $params
            ]);

            if ($this->trackingHelper->isEventMessageQueueAvailable(self::EVENT)) {
                $this->publisher->publish(self::EVENT, $eventClientAction, $wishlist->getStore()->getStoreId());
            } else {
                $this->sender->send(self::EVENT, $eventClientAction, $wishlist->getStore()->getId());
            }
        } catch (\Exception $e) {
            if (!$e instanceof ApiException) {
                $this->loggerHelper->getLogger()->error($e);
            }
        }
    }
}
