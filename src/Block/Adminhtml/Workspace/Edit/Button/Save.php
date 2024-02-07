<?php

namespace Synerise\Integration\Block\Adminhtml\Workspace\Edit\Button;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Ui\Component\Control\Container;

class Save extends Generic implements ButtonProviderInterface
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
                                'targetName' => 'synerise_workspace_form.synerise_workspace_form',
                                'actionName' => 'save',
//                                'params' => [false, $this->context->getRequest()->getParams()]
                                'params' => [false]
                            ],
                        ],
                    ],
                ],
            ],
            'class_name' => Container::SPLIT_BUTTON,
            'options' => $this->getOptions(),
        ];
    }

    /**
     * Retrieve options
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            [
                'id_hard' => 'save_and_new',
                'label' => __('Save & New'),
                'data_attribute' => [
                    'mage-init' => [
                        'buttonAdapter' => [
                            'actions' => [
                                [
                                    'targetName' => 'synerise_workspace_form.synerise_workspace_form',
                                    'actionName' => 'save',
                                    'params' => [true, ['back' => 'new']]
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'id_hard' => 'save_and_close',
                'label' => __('Save & Close'),
                'data_attribute' => [
                    'mage-init' => [
                        'buttonAdapter' => [
                            'actions' => [
                                [
                                    'targetName' => 'synerise_workspace_form.synerise_workspace_form',
                                    'actionName' => 'save',
                                    'params' => [true]
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        ];
    }
}
