<?php

namespace Synerise\Integration\Model\Config\Source\PageTracking;

use Magento\Framework\Data\OptionSourceInterface;

class Host implements OptionSourceInterface
{
    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => 'web.snrbox.com',
                'label' => 'azure'
            ],
            [
                'value' => 'web.geb.snrbox.com',
                'label' => 'gcp'
            ]
        ];
    }
}
