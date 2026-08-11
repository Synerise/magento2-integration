<?php

namespace Synerise\Integration\Helper\Order;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;

class UuidEmailResolver
{
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(CustomerRepositoryInterface $customerRepository)
    {
        $this->customerRepository = $customerRepository;
    }

    /**
     * Email used to derive the transaction uuid: a registered customer's CURRENT account
     * email (so a stale order snapshot still attributes to the live profile), else the fallback.
     *
     * @param Order $order
     * @param string|null $fallbackEmail
     * @return string|null
     */
    public function resolve(Order $order, ?string $fallbackEmail): ?string
    {
        $customerId = $order->getCustomerId();
        if ($customerId) {
            try {
                return $this->customerRepository->getById((int) $customerId)->getEmail();
            } catch (NoSuchEntityException | LocalizedException $e) {
                // deleted/unreadable customer -> fall back
            }
        }

        return $fallbackEmail;
    }
}
