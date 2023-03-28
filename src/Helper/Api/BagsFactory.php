<?php

namespace Synerise\Integration\Helper\Api;

use Magento\Framework\ObjectManagerInterface;
use Synerise\Integration\Model\ApiConfig;

class BagsFactory
{
    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->_objectManager = $objectManager;
    }

    /**
     * @param ApiConfig $apiConfig
     * @return Bags
     */
    public function create(ApiConfig $apiConfig): Bags
    {
        return $this->_objectManager->create(Bags::class, ['apiConfig' => $apiConfig]);
    }
}