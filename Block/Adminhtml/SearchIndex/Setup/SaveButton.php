<?php

namespace Synerise\Integration\Block\Adminhtml\SearchIndex\Setup;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Synerise\Integration\Block\Adminhtml\SearchIndex\GenericButton;

class SaveButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * Get button data
     *
     * @return array
     */
    public function getButtonData()
    {
        return [
            'label' => __('Save'),
            'class' => 'save primary',
            'data_attribute' => [
                'mage-init' => [
                    'buttonAdapter' => [
                        'actions' => [
                            [
                                'targetName' => 'synerise_searchindex_setup_form.synerise_searchindex_setup_form',
                                'actionName' => 'save',
                                'params' => [false]
                            ],
                        ],
                    ],
                ],
            ]
        ];
    }
}
