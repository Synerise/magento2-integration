<?php

namespace Synerise\Integration\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Environment implements OptionSourceInterface
{
    public const API_HOST = [
        0 => 'https://api.synerise.com',
        1 => 'https://api.geb.synerise.com'
    ];

    public const TRACKER_HOST = [
        0 => 'web.snrbox.com',
        1 => 'web.geb.snrbox.com'
    ];

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => '0',
                'label' => 'Microsoft Azure'
            ],
            [
                'value' => '1',
                'label' => 'Google Cloud Platform'
            ]
        ];
    }
}
