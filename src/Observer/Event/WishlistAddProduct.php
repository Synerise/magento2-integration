<?php

namespace Synerise\Integration\Observer\Event;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Helper\Tracking\State;
use Synerise\Integration\Model\Tracking\ConfigFactory;
use Synerise\Integration\SyneriseApi\Mapper\Event\WishlistAdd;
use Synerise\Integration\SyneriseApi\Sender\Event;
use Synerise\Integration\MessageQueue\Publisher\Event as Publisher;

class WishlistAddProduct implements ObserverInterface
{
    public const EVENT = 'wishlist_add_product';

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
     * @var WishlistAdd
     */
    protected $wishlistAdd;

    /**
     * @var Publisher
     */
    protected $publisher;

    /**
     * @var Event
     */
    protected $sender;

    /**
     * @param ConfigFactory $configFactory
     * @param Logger $loggerHelper
     * @param State $stateHelper
     * @param WishlistAdd $wishlistAdd
     * @param Publisher $publisher
     * @param Event $sender
     */
    public function __construct(
        ConfigFactory $configFactory,
        Logger $loggerHelper,
        State $stateHelper,
        WishlistAdd $wishlistAdd,
        Publisher $publisher,
        Event $sender
    ) {
        $this->configFactory = $configFactory;
        $this->loggerHelper = $loggerHelper;
        $this->stateHelper = $stateHelper;
        $this->wishlistAdd = $wishlistAdd;
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
            /** @var \Magento\Wishlist\Model\Wishlist $wishlist */
            $wishlist = $observer->getEvent()->getWishlist();
            if (!$wishlist->getCustomerId()) {
                return;
            }

            $storeId = $wishlist->getStore()->getId();
            $config = $this->configFactory->create($storeId);
            if (!$config->isEventTrackingEnabled(self::EVENT)) {
                return;
            }

            $eventClientAction = $this->wishlistAdd->prepareRequest(
                self::EVENT,
                $wishlist,
                $observer->getEvent()->getProduct()
            );

            if ($config->isEventMessageQueueEnabled(self::EVENT)) {
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
