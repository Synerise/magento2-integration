<?php
namespace Synerise\Integration\Block\Adminhtml\Workspace\Edit;


/**
 * Adminhtml blog post edit form
 */
class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * Init form
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('workspace_form');
        $this->setTitle(__('Workspace Information'));
    }

    /**
     * Prepare form
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        /** @var \Synerise\Integration\Model\Workspace $model */
        $model = $this->getDataObject();

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post']]
        );

        $form->setHtmlIdPrefix('workspace_');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('Workspace'), 'class' => 'fieldset-wide']
        );

        if ($model && $model->getId()) {
            $fieldset->addField('id', 'hidden', ['name' => 'id']);
        }

        $afterElementHtml = '<p class="nm">Api keys can be generated in Synerise application under <a href="https://app.synerise.com/spa/modules/settings/apikeys/list" target="_blank">Settings > API Keys</a>.<br /><small>Create a <i>Business Profile</i> api key with appropriate permissions.</small></p>';

        $fieldset->addField(
            'api_key',
            'obscure',
            [
                'name' => 'api_key',
                'label' => __('Api Key'),
                'title' => __('Api Key'),
                'required' => true,
                'after_element_html' => $afterElementHtml
            ]
        );

        if($model) {
            $form->setValues($model->getData());
        }
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}