<?php

namespace Synerise\Integration\Search\Container;

use Magento\Framework\Exception\ValidatorException;
use Synerise\Integration\Model\Workspace\ConfigFactory as WorkspaceConfigFactory;
use Synerise\Integration\SyneriseApi\ConfigFactory as ApiConfigFactory;
use Synerise\Integration\SyneriseApi\InstanceFactory as ApiInstanceFactory;
use Synerise\ItemsSearchConfigApiClient\Api\SearchConfigurationApi;

class Indices
{
    /**
     * @var ApiConfigFactory
     */
    private $apiConfigFactory;

    /**
     * @var ApiInstanceFactory
     */
    private $apiInstanceFactory;

    /**
     * @var WorkspaceConfigFactory
     */
    private $workspaceConfigFactory;

    /**
     * @var mixed
     */
    private $loaded;

    public function __construct(
        ApiConfigFactory $apiConfigFactory,
        ApiInstanceFactory $apiInstanceFactory,
        WorkspaceConfigFactory $workspaceConfigFactory
    ){
        $this->apiConfigFactory = $apiConfigFactory;
        $this->apiInstanceFactory = $apiInstanceFactory;
        $this->workspaceConfigFactory = $workspaceConfigFactory;
    }

    /**
     * Get indices for store ID
     *
     * @param int $storeId
     * @return mixed|\Synerise\ItemsSearchConfigApiClient\Model\Error|\Synerise\ItemsSearchConfigApiClient\Model\PaginatedSearchConfigsSchema
     * @throws ValidatorException
     * @throws \Synerise\ItemsSearchConfigApiClient\ApiException
     */
    public function getIndices(int $storeId)
    {
        if (!isset($this->loaded[$storeId])) {
            $searchConfigApi = $this->getSearchConfigApiInstance($storeId);
            $this->loaded[$storeId] = $searchConfigApi->getIndicesConfigsV2()->getData();
        }

        return $this->loaded[$storeId];
    }

    /**
     * Get index if exists
     *
     * @param int $storeId
     * @param string $indexId
     * @return mixed|\Synerise\ItemsSearchConfigApiClient\Model\SearchConfigSchema|null
     * @throws ValidatorException
     * @throws \Synerise\ItemsSearchConfigApiClient\ApiException
     */
    public function getIndex(int $storeId, string $indexId) {
        if ($indices = $this->getIndices($storeId)) {
            foreach ($indices as $index) {
                if ($index->getIndexId() == $indexId) {
                    return $index;
                }
           }
        }

        return null;
    }

    /**
     * Get AI Search Api instance
     *
     * @param int $storeId
     * @return SearchConfigurationApi
     * @throws ValidatorException
     */
    private function getSearchConfigApiInstance(int $storeId): SearchConfigurationApi
    {
        return $this->apiInstanceFactory->createApiInstance(
            'search-config',
            $this->apiConfigFactory->create($storeId),
            $this->workspaceConfigFactory->create($storeId)
        );
    }
}