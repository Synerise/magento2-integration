<?php

namespace Synerise\Integration\Model\Config\Source\Synchronization;

class Model implements \Magento\Framework\Data\OptionSourceInterface
{
    const OPTIONS = [
        'product'       => 'Products',
        'order'         => 'Orders',
        'customer'      => 'Customers',
        'subscriber'    => 'Subscribers'
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
