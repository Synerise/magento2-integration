<?php

namespace Synerise\Integration\SyneriseApi;

use Magento\Framework\ObjectManagerInterface;
use Synerise\Integration\Model\WorkspaceInterface;

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
     * @param ConfigInterface $apiConfig
     * @param WorkspaceInterface $workspace
     * @return Authenticator
     */
    public function create(ConfigInterface $apiConfig, WorkspaceInterface $workspace): Authenticator
    {
        return $this->objectManager->create(Authenticator::class, [
            'apiConfig' => $apiConfig,
            'workspace' => $workspace
        ]);
    }
}
