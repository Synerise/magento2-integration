<?php

namespace Synerise\Integration\Model\Search;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Filesystem;
use Magento\Framework\Session\Config;

class SessionConfig extends Config
{
    public function __construct(
        \Magento\Framework\ValidatorFactory $validatorFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Stdlib\StringUtils $stringHelper,
        \Magento\Framework\App\RequestInterface $request,
        Filesystem $filesystem,
        DeploymentConfig $deploymentConfig,
        $scopeType,
        $lifetimePath = self::XML_PATH_COOKIE_LIFETIME
    )
    {
        parent::__construct($validatorFactory, $scopeConfig, $stringHelper, $request, $filesystem, $deploymentConfig, $scopeType);
        parent::setCookieLifetime(600);
    }
}