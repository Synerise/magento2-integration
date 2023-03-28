<?php

namespace Synerise\Integration\Observer\Event\Wishlist;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Helper\Api\Event\Favorites;
use Synerise\Integration\Helper\Api\Identity;
use Synerise\Integration\Helper\Api\Event\Cart;
use Synerise\Integration\Helper\Event;
use Synerise\Integration\Helper\MessageQueue;
use Synerise\Integration\Observer\AbstractObserver;

abstract class AbstractWishlistEvent extends AbstractObserver implements ObserverInterface
{
    /**
     * @var Event
     */
    protected $eventsHelper;

    /**
     * @var MessageQueue
     */
    protected $queueHelper;

    /**
     * @var Cart
     */
    protected $favoritesHelper;

    /**
     * @var Identity
     */
    protected $identityHelper;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface      $logger,
        Event                $eventsHelper,
        MessageQueue         $queueHelper,
        Favorites            $favoritesHelper,
        Identity             $identityHelper
    ) {
        $this->eventsHelper = $eventsHelper;
        $this->queueHelper = $queueHelper;
        $this->favoritesHelper = $favoritesHelper;
        $this->identityHelper = $identityHelper;

        parent::__construct($scopeConfig, $logger);
    }

    abstract public function execute(Observer $observer);
}
