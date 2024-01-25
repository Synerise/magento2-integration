<?php

namespace Synerise\Integration\Model\Config\Source\Debug;

use Magento\Framework\Data\OptionSourceInterface;

class Exclude implements OptionSourceInterface
{
    public const OPTIONS = [
        self::EXCEPTION_PRODUCT_NOT_FOUND => 'Product not found',
        self::EXCEPTION_CLIENT_MERGE_FAIL => 'Client merge failed'
    ];

    public const EXCEPTION_CLIENT_MERGE_FAIL = 'client_merge';

    public const EXCEPTION_PRODUCT_NOT_FOUND = 'product_not_found';

    public const XML_PATH_DEBUG_LOGGER_EXCLUDE = 'synerise/debug/logger_exclude';

    /**
     * @inheritDoc
     */
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
