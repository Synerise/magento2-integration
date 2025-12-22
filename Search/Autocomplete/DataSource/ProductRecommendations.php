<?php

namespace Synerise\Integration\Search\Autocomplete\DataSource;

use Magento\Store\Model\StoreManagerInterface;
use Synerise\Api\Recommendations\Models\PostRecommendationsRequest;
use Synerise\Integration\Helper\Tracking\Cookie;
use Synerise\Integration\SyneriseApi\ConfigFactory as ApiConfigFactory;
use Synerise\Sdk\Api\ClientBuilderFactoryInterface;

class ProductRecommendations implements DataSourceInterface
{
    /**
     * @var DataFactory
     */
    private $dataFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Cookie
     */
    private $cookieHelper;

    /**
     * @var ApiConfigFactory
     */
    private $apiConfigFactory;

    /**
     * @var ClientBuilderFactoryInterface
     */
    private $clientBuilderFactory;

    /**
     * @var array
     */
    private $sourceConfig;

    public function __construct(
        DataFactory $dataFactory,
        StoreManagerInterface $storeManager,
        Cookie $cookieHelper,
        ApiConfigFactory $apiConfigFactory,
        ClientBuilderFactoryInterface $clientBuilderFactory,
        array $sourceConfig
    ) {
        $this->dataFactory = $dataFactory;
        $this->storeManager = $storeManager;
        $this->cookieHelper = $cookieHelper;
        $this->apiConfigFactory = $apiConfigFactory;
        $this->clientBuilderFactory = $clientBuilderFactory;
        $this->sourceConfig = $sourceConfig;
    }

    public function get(): ?DataInterface
    {
        $storeId = $this->storeManager->getStore()->getId();

        $correlationId = null;
        $ids = [];

        $client = $this->clientBuilderFactory->create($this->apiConfigFactory->create($storeId));
        $request = $this->getRequest(
            $this->sourceConfig['campaign_id'],
            $this->cookieHelper->getSnrsUuid()
        );

        $response = $client->recommendations()->recommendations()->v2()->recommend()->campaigns()->post($request)->wait();
        if ($response) {
            foreach ($response->getData() as $key => $item) {
                $additionalData = $item->getAdditionalData() ?: [];
                if (isset($additionalData['entity_id'])) {
                    $ids[$additionalData['entity_id']] = $key+1;
                }
            }
            $extras = $response->getExtras();
            $correlationId = $extras ? $extras->getCorrelationId() : null;
        }

        return $this->dataFactory->create([
            'header' => $this->sourceConfig['header'],
            'values' => $ids,
            'correlation_id' => $correlationId
        ]);
    }

    protected function getRequest($campaignId, $uuid): PostRecommendationsRequest
    {
        $request = new PostRecommendationsRequest();
        $request->setCampaignId($campaignId);
        $request->setClientUUID($uuid);

        return $request;
    }
}