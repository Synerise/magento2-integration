<?php

namespace Synerise\Integration\Model\Config\Source\PageTracking;

class Host implements \Magento\Framework\Data\OptionSourceInterface
{
    public function toOptionArray()
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
