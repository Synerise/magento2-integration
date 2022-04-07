<?php

namespace Synerise\Integration\Model\Config\Source\Customers;

use \Magento\Customer\Model\ResourceModel\Attribute\CollectionFactory;
use \Magento\Framework\Data\OptionSourceInterface;

class Attributes implements OptionSourceInterface
{
    const REQUIRED = [
        'email',
        'entity_id',
        'firstname',
        'lastname'
    ];

    const EXCLUDED = [
        'password_hash'
    ];

    /** @var CollectionFactory */
    private $collectionFactory;

    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray()
    {
        $collection = $this->collectionFactory->create();
        $options = [];

        $excluded = array_merge(self::REQUIRED, self::EXCLUDED);

        foreach ($collection as $item) {
            if (!in_array($item->getAttributeCode(), $excluded)) {
                $options[] = [
                    'value' => $item->getAttributeCode(),
                    'label' => $item->getAttributeCode()
                ];
            }
        }

        return $options;
    }
}