<?php

namespace Synerise\Integration\Model\Config\Source\Search\Attributes;

use Magento\Framework\Data\OptionSourceInterface;
use Synerise\Integration\Search\Attributes\Config;

class Sortable implements OptionSourceInterface
{
    /**
     * @var Config
     */
    protected $config;

    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray()
    {
        $options = [];
        $labels = $this->config->getFrontendLabels();
        foreach ($this->config->getSortable() as $code => $mappedField) {
            $options[] = [
                'value' => $mappedField,
                'label' =>$labels[$code]
            ];
        }

        return $options;
    }

}