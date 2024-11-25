<?php

namespace Synerise\Integration\Model\Config\Source\Products\Attributes;

use Magento\Framework\Data\OptionSourceInterface;

class Format implements OptionSourceInterface
{
    public const OPTION_ID = 0;

    public const OPTION_LABEL = 1;

    public const OPTION_ID_AND_LABEL = 2;

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::OPTION_ID, 'label' => 'ID'],
            ['value' => self::OPTION_LABEL, 'label' => 'Label'],
            ['value' => self::OPTION_ID_AND_LABEL, 'label' => 'ID & Label']
        ];
    }
}