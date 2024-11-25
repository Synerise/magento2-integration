<?php

namespace Synerise\Integration\Block\Adminhtml\SearchIndex\Assign;

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
                                'targetName' => 'synerise_searchindex_assign_form.synerise_searchindex_assign_form',
                                'actionName' => 'save',
                                'params' => [
                                    true,
                                    ['store_id' => $this->context->getRequest()->getParam('store')]
                                ]
                            ],
                        ],
                    ],
                ],
            ]
        ];
    }
}
