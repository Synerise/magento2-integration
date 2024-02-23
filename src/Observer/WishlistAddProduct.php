<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\SyneriseApi\Mapper\WishlistAddProduct as Mapper;
use Synerise\Integration\SyneriseApi\Sender\Event;
use Synerise\Integration\MessageQueue\Publisher\Event as Publisher;
use Synerise\Integration\Helper\Tracking;

class WishlistAddProduct implements ObserverInterface
{
    public const EVENT = 'wishlist_add_product';

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
     * @param Logger $loggerHelper
     * @param Tracking $trackingHelper
     * @param Mapper $mapper
     * @param Publisher $publisher
     * @param Event $sender
     */
    public function __construct(
        Logger $loggerHelper,
        Tracking $trackingHelper,
        Mapper $mapper,
        Publisher $publisher,
        Event $sender
    ) {
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
            /** @var \Magento\Wishlist\Model\Wishlist $wishlist */
            $wishlist = $observer->getEvent()->getWishlist();
            if (!$wishlist->getCustomerId()) {
                return;
            }

            $storeId = $wishlist->getStore()->getId();
            if (!$this->trackingHelper->isEventTrackingAvailable(self::EVENT, $storeId)) {
                return;
            }

            $eventClientAction = $this->mapper->prepareRequest(
                self::EVENT,
                $wishlist,
                $observer->getEvent()->getProduct()
            );

            if ($this->trackingHelper->isEventMessageQueueAvailable(self::EVENT, $storeId)) {
                $this->publisher->publish(self::EVENT, $eventClientAction, $storeId);
            } else {
                $this->sender->send(self::EVENT, $eventClientAction, $storeId);
            }
        } catch (\Exception $e) {
            if (!$e instanceof ApiException) {
                $this->loggerHelper->error($e);
            }
        }
    }
}
