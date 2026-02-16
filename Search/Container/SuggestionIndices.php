<?php

namespace Synerise\Integration\Search\Container;

use Magento\Framework\Exception\ValidatorException;
use Synerise\Integration\Model\Workspace\ConfigFactory as WorkspaceConfigFactory;
use Synerise\Integration\SyneriseApi\ConfigFactory as ApiConfigFactory;
use Synerise\Integration\SyneriseApi\InstanceFactory as ApiInstanceFactory;
use Synerise\ItemsSearchConfigApiClient\Api\SuggestionsConfigurationApi;

class SuggestionIndices
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
     * @return \Synerise\ItemsSearchConfigApiClient\Model\SuggestionIndexSchema[]|null
     * @throws ValidatorException|\InvalidArgumentException
     * @throws \Synerise\ItemsSearchConfigApiClient\ApiException
     */
    public function getIndices(int $storeId)
    {
        if (!isset($this->loaded[$storeId])) {
            $searchConfigApi = $this->getSuggestionConfigApiInstance($storeId);
            $this->loaded[$storeId] = $searchConfigApi->getSuggestionsIndices()->getData();
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
     * @return SuggestionsConfigurationApi
     * @throws ValidatorException
     */
    private function getSuggestionConfigApiInstance(int $storeId): SuggestionsConfigurationApi
    {
        return $this->apiInstanceFactory->createApiInstance(
            'suggestions-config',
            $this->apiConfigFactory->create($storeId),
            $this->workspaceConfigFactory->create($storeId)
        );
    }
}