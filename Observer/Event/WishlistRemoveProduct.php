<?php

namespace Synerise\Integration\Observer\Event;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Wishlist\Model\Wishlist;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Helper\Tracking\State;
use Synerise\Integration\Model\Tracking\ConfigFactory;
use Synerise\Integration\SyneriseApi\Mapper\Event\WishlistRemove;
use Synerise\Integration\SyneriseApi\Sender\Event;
use Synerise\Integration\MessageQueue\Publisher\Event as Publisher;

class WishlistRemoveProduct implements ObserverInterface
{
    public const EVENT = 'wishlist_item_delete_after';

    /**
     * @var Wishlist
     */
    protected $wishlist;

    /**
     * @var ConfigFactory
     */
    protected $configFactory;

    /**
     * @var Logger
     */
    protected $loggerHelper;

    /**
     * @var State
     */
    protected $stateHelper;

    /**
     * @var WishlistRemove
     */
    protected $wishlistRemove;

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
     * @param ConfigFactory $configFactory
     * @param Logger $loggerHelper
     * @param State $stateHelper
     * @param WishlistRemove $wishlistRemove
     * @param Publisher $publisher
     * @param Event $sender
     */
    public function __construct(
        Wishlist $wishlist,
        ConfigFactory $configFactory,
        Logger $loggerHelper,
        State $stateHelper,
        WishlistRemove $wishlistRemove,
        Publisher $publisher,
        Event $sender
    ) {
        $this->wishlist = $wishlist;
        $this->configFactory = $configFactory;
        $this->loggerHelper = $loggerHelper;
        $this->stateHelper = $stateHelper;
        $this->wishlistRemove = $wishlistRemove;
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
        if ($this->stateHelper->isAdminStore()) {
            return;
        }

        try {
            /** @var \Magento\Wishlist\Model\Item $item */
            $item = $observer->getItem();

            $storeId = $item->getStoreId();
            $config = $this->configFactory->create($storeId);
            if (!$config->isEventTrackingEnabled(self::EVENT)) {
                return;
            }

            /** @var Wishlist $wishlist */
            $wishlist = $this->wishlist->load($item->getWishlistId());
            if (!$wishlist->getCustomerId()) {
                return;
            }

            $customEventRequest = $this->wishlistRemove->prepareRequest(
                self::EVENT,
                $wishlist,
                $observer->getEvent()->getProduct()
            );

            if ($config->isEventMessageQueueEnabled(self::EVENT)) {
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
