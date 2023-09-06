<?php

namespace Synerise\Integration\Model\Config\Source\Debug;

class Exclude implements \Magento\Framework\Data\OptionSourceInterface
{
    const OPTIONS = [
        self::ERROR_PRODUCT_NOT_FOUND => 'Product not found',
        self::ERROR_CLIENT_MERGE => 'Client merge failed'
    ];

    const ERROR_CLIENT_MERGE = 'client_merge';

    const ERROR_PRODUCT_NOT_FOUND = 'product_not_found';

    const XML_PATH_DEBUG_LOGGER_EXCLUDE = 'synerise/debug/logger_exclude';

    public function toOptionArray(): array
    {
        $options = [];
        foreach (self::OPTIONS as $value => $label) {
            $options[] = [
                'value' => $value,
                'label' => $label
            ];
        }

        return $options;
    }
}