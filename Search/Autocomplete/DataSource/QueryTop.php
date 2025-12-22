<?php

namespace Synerise\Integration\Search\Autocomplete\DataSource;

use Magento\Store\Model\StoreManagerInterface;
use Synerise\Api\Search\Search\V2\Indices\Item\EscapedList\ListPostRequestBody;
use Synerise\Integration\Helper\Tracking\Cookie;
use Synerise\Integration\SyneriseApi\ConfigFactory as ApiConfigFactory;
use Synerise\Sdk\Api\ClientBuilderFactoryInterface;

class QueryTop implements DataSourceInterface
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

        $client = $this->clientBuilderFactory->create($this->apiConfigFactory->create($storeId));
        $request = $this->getRequest(
            $this->cookieHelper->getSnrsUuid(),
            $this->sourceConfig['limit']
        );

        if (empty($this->sourceConfig['index_id'])) {
            throw new \InvalidArgumentException(sprintf('Suggestions index not set for store: %d.', $storeId));
        }

        $values = [];
        $response = $client->search()->search()->v2()->indices()->byIndexId($this->sourceConfig['index_id'])
            ->escapedList()->post($request)->wait();

        $suggestions = $response->getData() ?: [];
        foreach ($suggestions as $suggestion) {
            $additionalData = $suggestion->getAdditionalData();
            if (isset($additionalData['suggestion'])) {
                $values[] = $additionalData['suggestion'];
            }
        }

        $extras = $response->getExtras();
        $correlationId = $extras ? $extras->getCorrelationId() : null;

        return $this->dataFactory->create([
            'header' => $this->sourceConfig['header'],
            'values' => $values,
            'correlation_id' => $correlationId
        ]);
    }

    protected function getRequest(string $uuid, int $limit = 8): ListPostRequestBody
    {
        $request = new ListPostRequestBody();
        $request->setClientUUID($uuid);
        $request->setLimit($limit);

        return $request;
    }
}