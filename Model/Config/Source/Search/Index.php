<?php

namespace Synerise\Integration\Model\Config\Source\Search;

use Magento\Config\Block\System\Config\Form;
use Magento\Framework\Data\OptionSourceInterface;
use Synerise\Integration\Search\Container\Indices;

class Index implements OptionSourceInterface
{
    /**
     * @var Form
     */
    private $form;

    /**
     * @var Indices
     */
    private $indices;

    public function __construct(
        Form $form,
        Indices $indices
    ){
        $this->form = $form;
        $this->indices = $indices;
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray()
    {
        $options = [
            [
                'value' => '',
                'label' => ''
            ]
        ];

        if ($this->form->getStoreCode()) {
            $indices = $this->indices->getIndices($this->form->getStoreCode());
            foreach ($indices as $index) {
                $options[] = [
                    'value' => $index->getIndexId(),
                    'label' => $index->getIndexName()
                ];
            }
        }

        return $options;
    }
}