<?php

namespace Synerise\Integration\Model\Config\Source\MessageQueue;

class Connection implements \Magento\Framework\Data\OptionSourceInterface
{
    const CONFIG_PATH = 'synerise/queue/connection';

    const OPTIONS = [
        'db' => 'db',
        'amqp' => 'amqp'
    ];

    public function toOptionArray()
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
