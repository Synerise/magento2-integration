<?php

namespace Synerise\Integration\ViewModel\Recommendations;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Synerise\Api\Recommendations\Models\RecommendationResponseSchemaV2Materializer;
use Synerise\Integration\Helper\Tracking\Cookie;
use Synerise\Integration\Model\Product\CollectionBuilder;
use Synerise\Integration\Model\Product\RecommendationDataProvider;

class Data extends DataObject implements ArgumentInterface
{
    /**
     * @var RecommendationDataProvider
     */
    private $dataProvider;

    /**
     * @var CollectionBuilder
     */
    private $collectionBuilder;

    /**
     * @var Cookie
     */
    private $cookieHelper;

    /**
     * @var RecommendationResponseSchemaV2Materializer
     */
    private $response;

    public function __construct(
        RecommendationDataProvider $dataProvider,
        CollectionBuilder $collectionBuilder,
        Cookie $cookieHelper,
        array $data = []
    ) {
        $this->dataProvider = $dataProvider;
        $this->collectionBuilder = $collectionBuilder;
        $this->cookieHelper = $cookieHelper;

        parent::__construct($data);
        $this->init();
    }

    protected function init()
    {
        $campaignId = $this->getCampaignId();
        $isStatic = $this->getStatic() ?? false;
        $storeId = $this->getStoreId() ?? null;

        if (!$campaignId) {
            throw new \InvalidArgumentException('Campaign ID is required');
        }

        $this->response = $this->dataProvider->getData(
            $campaignId,
            !$isStatic ? $this->cookieHelper->getSnrsUuid(): null,
            $storeId
        );

        return $this->response;
    }

    public function getCorrelationId()
    {
        $extras = $this->response ? $this->response->getExtras() : null;
        return $extras ? $extras->getCorrelationId() : null;
    }

    public function getProductCollection(): ?Collection
    {
        if (!$this->response) {
            return null;
        }

        $ids = [];
        foreach ($this->response->getData() as $item) {
            $additionalData = $item->getAdditionalData() ?: [];
            if (isset($additionalData['entity_id'])) {
                $ids[] = $additionalData['entity_id'];
            }
        }

        return $this->collectionBuilder->build($ids, $this->getStoreId(), $this->getPageSize());
    }
}