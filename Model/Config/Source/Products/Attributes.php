<?php

namespace Synerise\Integration\Model\Config\Source\Products;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

class Attributes implements OptionSourceInterface
{
    public const REQUIRED = [
        'entity_id',
        'sku',
        'price',
        'image',
        'visibility'
    ];

    public const REQUIRED_FOR_SELECT = [
        'tax_class_id',
        'special_price'
    ];

    public const XML_PATH_PRODUCT_ATTRIBUTES = 'synerise/product/attributes';

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
    public function toOptionArray(): array
    {
        $collection = $this->collectionFactory->create();
        $options = [];

        foreach ($collection as $item) {
            if (!in_array($item->getAttributeCode(), self::REQUIRED)) {
                $options[] = [
                    'value' => $item->getAttributeCode(),
                    'label' => $item->getAttributeCode()
                ];
            }
        }

        foreach (Price::OPTIONS as $item) {
            $options[] = [
                'value' => $item['value'],
                'label' => $item['value']
            ];
        }

        return $options;
    }
}
