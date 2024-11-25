<?php

namespace Synerise\Integration\SyneriseApi\Catalogs;

use Magento\Framework\ObjectManagerInterface;

class AttributeValidatorFactory
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
     * Create Attribute Validator with api config
     *
     * @param int $catalogId
     * @param int $storeId
     * @return AttributeValidator
     */
    public function create(int $catalogId, int $storeId): AttributeValidator
    {
        return $this->objectManager->create(AttributeValidator::class, [
            'storeId' => $storeId,
            'catalogId' => $catalogId
        ]);
    }
}
