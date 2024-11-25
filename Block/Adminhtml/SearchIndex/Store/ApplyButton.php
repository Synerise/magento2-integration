<?php

namespace Synerise\Integration\Block\Adminhtml\SearchIndex\Store;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Synerise\Integration\Block\Adminhtml\SearchIndex\GenericButton;

class ApplyButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * Get button data
     *
     * @return array
     */
    public function getButtonData()
    {
        return [
            'label' => __('Apply'),
            'class' => 'save primary',
            'data_attribute' => [
                'mage-init' => [
                    'buttonAdapter' => [
                        'actions' => [
                            [
                                'targetName' => 'synerise_searchindex_store_form.synerise_searchindex_store_form',
                                'actionName' => 'save',
                                'params' => [true]
                            ],
                        ],
                    ],
                ],
            ]
        ];
    }
}
