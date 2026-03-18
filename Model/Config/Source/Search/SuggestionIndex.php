<?php

namespace Synerise\Integration\Model\Config\Source\Search;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Model\StoreManagerInterface;
use Synerise\Integration\Search\Container\SuggestionIndices;

class SuggestionIndex implements OptionSourceInterface
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var SuggestionIndices
     */
    private $indices;

    public function __construct(
        RequestInterface $request,
        StoreManagerInterface $storeManager,
        SuggestionIndices $indices
    ){
        $this->request = $request;
        $this->storeManager = $storeManager;
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

        $storeId = $this->getStoreId();
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

    /**
     * Get store id by request
     *
     * @return int|mixed|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getStoreId()
    {
        $storeId = $this->request->getParam('store');
        if ($storeId) {
            return $storeId;
        }

        $websiteId = $this->request->getParam('website');
        if ($websiteId) {
            $website = $this->storeManager->getWebsite($websiteId);
            return $website->getDefaultStore()->getId();
        }

        return null;
    }
}