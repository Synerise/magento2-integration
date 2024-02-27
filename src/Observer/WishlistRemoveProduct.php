<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Wishlist\Model\Wishlist;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\SyneriseApi\Mapper\WishlistRemoveProduct as Mapper;
use Synerise\Integration\SyneriseApi\Sender\Event;
use Synerise\Integration\MessageQueue\Publisher\Event as Publisher;
use Synerise\Integration\Helper\Tracking;

class WishlistRemoveProduct implements ObserverInterface
{
    public const EVENT = 'wishlist_item_delete_after';

    /**
     * @var Wishlist
     */
    protected $wishlist;

    /**
     * @var Logger
     */
    protected $loggerHelper;

    /**
     * @var Tracking
     */
    protected $trackingHelper;

    /**
     * @var Mapper
     */
    protected $mapper;

    /**
     * @var Publisher
     */
    protected $publisher;

    /**
     * @var Event
     */
    protected $sender;

    /**
     * @param Wishlist $wishlist
     * @param Logger $loggerHelper
     * @param Tracking $trackingHelper
     * @param Mapper $mapper
     * @param Publisher $publisher
     * @param Event $sender
     */
    public function __construct(
        Wishlist $wishlist,
        Logger $loggerHelper,
        Tracking $trackingHelper,
        Mapper $mapper,
        Publisher $publisher,
        Event $sender
    ) {
        $this->wishlist = $wishlist;
        $this->loggerHelper = $loggerHelper;
        $this->trackingHelper = $trackingHelper;
        $this->mapper = $mapper;
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
        if ($this->trackingHelper->getContext()->isAdminStore()) {
            return;
        }

        try {
            /** @var \Magento\Wishlist\Model\Item $item */
            $item = $observer->getItem();

            $storeId = $item->getStoreId();
            if (!$this->trackingHelper->isEventTrackingAvailable(self::EVENT, $storeId)) {
                return;
            }

            /** @var Wishlist $wishlist */
            $wishlist = $this->wishlist->load($item->getWishlistId());
            if (!$wishlist->getCustomerId()) {
                return;
            }

            $customEventRequest = $this->mapper->prepareRequest(
                self::EVENT,
                $wishlist,
                $observer->getEvent()->getProduct()
            );

            if ($this->trackingHelper->isEventMessageQueueAvailable(self::EVENT, $storeId)) {
                $this->publisher->publish(self::EVENT, $customEventRequest, $storeId);
            } else {
                $this->sender->send(self::EVENT, $customEventRequest, $storeId);
            }
        } catch (\Exception $e) {
            if (!$e instanceof ApiException) {
                $this->loggerHelper->error($e);
            }
        }
    }
}
