<?php

namespace Synerise\Integration\Model\Config\Source\MessageQueue;

class Connection implements \Magento\Framework\Data\OptionSourceInterface
{
    const CONFIG_PATH = 'synerise/queue/connection';

    const TOPICS = [
        'synerise.queue.events',
        'synerise.queue.data.scheduler',
        'synerise.queue.data.item',
        'synerise.queue.data.customer.batch',
        'synerise.queue.data.customer.range',
        'synerise.queue.data.order.batch',
        'synerise.queue.data.order.range',
        'synerise.queue.data.product.batch',
        'synerise.queue.data.product.range',
        'synerise.queue.data.subscriber.batch',
        'synerise.queue.data.subscriber.range',
    ];

    const OPTIONS = [
        'db' => 'db',
        'amqp' => 'amqp'
    ];

    const MAX_RETRIES = 3;

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
