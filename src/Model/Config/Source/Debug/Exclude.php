<?php

namespace Synerise\Integration\Model\Config\Source\Debug;

class Exclude implements \Magento\Framework\Data\OptionSourceInterface
{
    const OPTIONS = [
        self::EXCEPTION_PRODUCT_NOT_FOUND => 'Product not found',
        self::EXCEPTION_CLIENT_MERGE_FAIL => 'Client merge failed'
    ];

    const EXCEPTION_CLIENT_MERGE_FAIL = 'client_merge';

    const EXCEPTION_PRODUCT_NOT_FOUND = 'product_not_found';

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