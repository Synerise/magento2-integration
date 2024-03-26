<?php

namespace Synerise\Integration\Helper\Tracking;

use Magento\Backend\Model\Auth\Session as BackedSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Area;
use Magento\Framework\App\ScopeResolverInterface;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\Store;

class State
{
    /**
     * @var BackedSession
     */
    protected $backendSession;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var ScopeResolverInterface
     */
    protected $scopeResolver;

    /**
     * @var AppState
     */
    protected $state;

    /**
     * @param BackedSession $backendSession
     * @param CustomerSession $customerSession
     * @param ScopeResolverInterface $scopeResolver
     * @param AppState $state
     */
    public function __construct(
        BackedSession $backendSession,
        CustomerSession $customerSession,
        ScopeResolverInterface $scopeResolver,
        AppState $state
    ) {
        $this->backendSession = $backendSession;
        $this->customerSession = $customerSession;
        $this->scopeResolver = $scopeResolver;
        $this->state = $state;
    }

    /**
     * Check if current area is frontend
     *
     * @return bool
     */
    public function isFrontend(): bool
    {
        try {
            return $this->state->getAreaCode() == Area::AREA_FRONTEND;
        } catch (LocalizedException $e) {
            return false;
        }
    }

    /**
     * Check is request use default scope
     *
     * @return bool
     */
    public function isAdminStore(): bool
    {
        return $this->isAdminLoggedIn() || $this->scopeResolver->getScope()->getCode() == Store::ADMIN_CODE;
    }

    /**
     * Check if user is logged in
     *
     * @return bool
     */
    public function isAdminLoggedIn(): bool
    {
        return $this->backendSession->getUser() && $this->backendSession->getUser()->getId();
    }

    /**
     * Check if customer is logged in
     *
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        return $this->customerSession->isLoggedIn();
    }
}
