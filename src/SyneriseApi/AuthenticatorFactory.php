<?php

namespace Synerise\Integration\SyneriseApi;

use Magento\Framework\ObjectManagerInterface;
use Synerise\Integration\SyneriseApi\Config as ApiConfig;

class AuthenticatorFactory
{
    /**
     * Object Manager instance
     *
     * @var ObjectManagerInterface
     */
    protected $objectManager = null;

    /**
     * Factory constructor
     *
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Create authenticator with api config
     *
     * @param ApiConfig $apiConfig
     * @return Authenticator
     */
    public function create(ApiConfig $apiConfig): Authenticator
    {
        return $this->objectManager->create(Authenticator::class, ['apiConfig' => $apiConfig]);
    }
}
