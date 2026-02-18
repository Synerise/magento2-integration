<?php

namespace Synerise\Integration\Model\Product;

use Magento\Store\Model\StoreManagerInterface;
use Synerise\Api\Recommendations\Models\PostRecommendationsRequest;
use Synerise\Api\Recommendations\Models\RecommendationResponseSchemaV2Materializer;
use Synerise\Integration\SyneriseApi\ConfigFactory as ApiConfigFactory;
use Synerise\Sdk\Api\ClientBuilderFactoryInterface;

class RecommendationDataProvider
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ApiConfigFactory
     */
    private $apiConfigFactory;

    /**
     * @var ClientBuilderFactoryInterface
     */
    private $clientBuilderFactory;

    public function __construct(
        StoreManagerInterface $storeManager,
        ApiConfigFactory $apiConfigFactory,
        ClientBuilderFactoryInterface $clientBuilderFactory
    ) {
        $this->storeManager = $storeManager;
        $this->apiConfigFactory = $apiConfigFactory;
        $this->clientBuilderFactory = $clientBuilderFactory;
    }

    /**
     * Get recommendations data
     *
     * @param string $campaignId
     * @param string|null $uuid
     * @param string|null $storeId
     * @return RecommendationResponseSchemaV2Materializer
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getData(string $campaignId, ?string $uuid = null, ?string $storeId = null): RecommendationResponseSchemaV2Materializer
    {
    $request = $this->prepareRequest($campaignId, $uuid);

    if ($storeId === null) {
        $storeId = $this->storeManager->getStore()->getId();
    }

    return $this->fetchRecommendations($request, $storeId);
    }

    /**
     * Fetch Recommendations via api
     *
     * @param PostRecommendationsRequest $request
     * @param string $storeId
     * @return RecommendationResponseSchemaV2Materializer
     * @throws \Exception
     */
    private function fetchRecommendations(
        PostRecommendationsRequest $request,
        string $storeId
    ): RecommendationResponseSchemaV2Materializer
    {
        $config = $this->apiConfigFactory->create($storeId);
        $client = $this->clientBuilderFactory->create($config);

        return $client->recommendations()->recommendations()->v2()
            ->recommend()->campaigns()->post($request)->wait();
    }

    /**
     * Prepare request for campaign
     *
     * @param string $campaignId
     * @param string|null $uuid
     * @return PostRecommendationsRequest
     */
    private function prepareRequest(string $campaignId, ?string $uuid = null): PostRecommendationsRequest
    {
        $request = new PostRecommendationsRequest();
        $request->setCampaignId($campaignId);
        $request->setClientUUID($uuid);

        return $request;
    }
}