<?php

namespace Synerise\Integration\Model\Config\Source\Products;

use Magento\Framework\Data\OptionSourceInterface;

class Price implements OptionSourceInterface
{
    public const XML_PATH_PRODUCT_PRICE = 'synerise/product/price';

    public const OPTIONS = [
        [
            'value' => 'regular_price',
            'label' => 'Regular Price'
        ],
        [
            'value' => 'final_price',
            'label' => 'Final Price'
        ]
    ];

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return self::OPTIONS;
    }
}
