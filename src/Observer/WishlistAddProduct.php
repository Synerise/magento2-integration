<?php

namespace Synerise\Integration\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Helper\Identity;
use Synerise\Integration\Helper\Event\Favorites;

class WishlistAddProduct extends AbstractObserver implements ObserverInterface
{
    const EVENT = 'wishlist_add_product';

    /**
     * @var Identity
     */
    protected $identityHelper;

    /**
     * @var Favorites
     */
    private $favoritesHelper;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        Favorites $favoritesHelper,
        Identity $identityHelper
    ) {

        $this->identityHelper = $identityHelper;
        $this->favoritesHelper = $favoritesHelper;

        parent::__construct($scopeConfig, $logger);
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
            $this->favoritesHelper->sendClientAddedProductToFavoritesEvent(
                $this->favoritesHelper->prepareClientAddedProductToFavoritesRequest(
                    self::EVENT,
                    $observer->getEvent()->getProduct(),
                    $this->identityHelper->getClientUuid()
                )
            );
        } catch (\Exception $e) {
            $this->logger->error('Synerise Api request failed', ['exception' => $e]);
        }
    }
}
