<?php

namespace Synerise\Integration\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class CustomerDeleteBehavior implements OptionSourceInterface
{
    public const SEND_EVENT = 'event';

    public const REMOVE = 'remove';

    public const IGNORE = 'ignore';

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::SEND_EVENT, 'label' => 'Send an Event'],
            ['value' => self::REMOVE, 'label' => 'Remove permanently'],
            ['value' => self::IGNORE, 'label' => 'Ignore']
        ];
    }
}
