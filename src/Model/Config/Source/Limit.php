<?php

namespace Synerise\Integration\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Limit implements OptionSourceInterface
{
    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => '10', 'label' => '10'],
            ['value' => '25', 'label' => '25'],
            ['value' => '50', 'label' => '50'],
            ['value' => '100', 'label' => '100'],
        ];
    }
}
