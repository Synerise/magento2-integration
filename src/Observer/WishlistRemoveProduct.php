<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Helper\Identity;
use Synerise\Integration\Helper\Event\Favorites;

class WishlistRemoveProduct  extends AbstractObserver implements ObserverInterface
{
    const EVENT = 'wishlist_item_delete_after';

    /**
     * @var Identity
     */
    protected $identityHelper;

    /**
     * @var Favorites
     */
    protected $favoritesHelper;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        LoggerInterface $logger,
        Identity $identityHelper,
        Favorites $favoritesHelper
    ) {
        $this->logger = $logger;
        $this->identityHelper = $identityHelper;
        $this->favoritesHelper = $favoritesHelper;
    }

    public function execute(Observer $observer)
    {
        if (!$this->isLiveEventTrackingEnabled(self::EVENT)) {
            return;
        }

        if ($this->identityHelper->isAdminStore()) {
            return;
        }

        try {
            $this->favoritesHelper->sendCustomEvent(
                $this->favoritesHelper->prepareClientRemovedProductFromFavoritesRequest(
                    self::EVENT,
                    $observer->getEvent()->getItem()->getProduct(),
                    $this->identityHelper->getClientUuid()
                )
            );

        } catch (\Exception $e) {
            $this->logger->error('Synerise Api request failed', ['exception' => $e]);
        }
    }
}
