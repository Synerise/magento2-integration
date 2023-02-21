<?php

namespace Synerise\Integration\Observer\Event\Cart;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\ValidatorException;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\Api\DefaultApi;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Helper\Api;
use Synerise\Integration\Helper\Api\Event\Favorites;
use Synerise\Integration\Helper\Api\Factory\DefaultApiFactory;
use Synerise\Integration\Helper\Api\Identity;
use Synerise\Integration\Helper\Api\Event\Cart;
use Synerise\Integration\Observer\AbstractObserver;

abstract class AbstractWishlistEvent extends AbstractObserver implements ObserverInterface
{
    /**
     * @var DefaultApiFactory
     */
    protected $defaultApiFactory;

    /**
     * @var Api
     */
    protected $apiHelper;

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
        LoggerInterface $logger,
        DefaultApiFactory $defaultApiFactory,
        Api $apiHelper,
        Favorites $favoritesHelper,
        Identity $identityHelper
    ) {
        $this->defaultApiFactory = $defaultApiFactory;
        $this->apiHelper = $apiHelper;
        $this->favoritesHelper = $favoritesHelper;
        $this->identityHelper = $identityHelper;

        parent::__construct($scopeConfig, $logger);
    }

    abstract public function execute(Observer $observer);

    /**
     * @param int|null $storeId
     * @return DefaultApi
     * @throws ValidatorException
     * @throws ApiException
     */
    public function getDefaultApiInstance(?int $storeId = null): DefaultApi
    {
        return $this->defaultApiFactory->get($this->apiHelper->getApiConfigByScope($storeId));
    }
}
