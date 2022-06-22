<?php
namespace Synerise\Integration\Block\Adminhtml\Workspace;

class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Initialize Workspace edit block
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'id';
        $this->_blockGroup = 'Synerise_Integration';
        $this->_controller = 'adminhtml_workspace';

        parent::_construct();

        if ($this->_isAllowedAction('Synerise_Integration::workspace_add')) {
            $this->buttonList->update('save', 'label', __('Save Workspace'));
        } else {
            $this->buttonList->remove('save');
        }

        if ($this->_isAllowedAction('Synerise_Integration::workspace_delete')) {
            $this->buttonList->update('delete', 'label', __('Delete Workspace'));
        } else {
            $this->buttonList->remove('delete');
        }
    }

    /**
     * Retrieve text for header element depending on loaded Workspace
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        return __('New Workspace');
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }

    /**
     * Getter of url for "Save and Continue" button
     * tab_id will be replaced by desired by JS later
     *
     * @return string
     */
    protected function _getSaveAndContinueUrl()
    {
        return $this->getUrl('workspace/*/save', ['_current' => true, 'back' => 'edit', 'active_tab' => '']);
    }
}