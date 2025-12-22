<?php

namespace Synerise\Integration\Model\Config\Source\Search;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\OptionSourceInterface;
use Synerise\Integration\Search\Container\SuggestionIndices;

class SuggestionIndex implements OptionSourceInterface
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var SuggestionIndices
     */
    private $indices;

    public function __construct(
        RequestInterface $request,
        SuggestionIndices $indices
    ){
        $this->request = $request;
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

        $storeId = $this->request->getParam('store');
        if ($storeId) {
            $indices = $this->indices->getIndices($storeId) ?: [];
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