<?php
namespace Synerise\Integration\Ui\DataProvider\SearchIndex\Setup;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Synerise\Integration\Model\ResourceModel\SearchIndex\CollectionFactory;
use Synerise\Integration\Search\Container\Indices;
use Synerise\Integration\Search\Attributes\Config;
use Synerise\ItemsSearchConfigApiClient\Model\FacetableAttributesSchema;
use Synerise\ItemsSearchConfigApiClient\Model\FilterableAttributesSchema;
use Synerise\ItemsSearchConfigApiClient\Model\SortableAttributesSchema;

class DataProvider extends AbstractDataProvider
{
    /**
     * @var mixed
     */
    protected $loadedData;

    /**
     * @var Config
     */
    protected $attributesConfig;

    /**
     * @var Indices
     */
    protected $indices;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param Config $attributesConfig
     * @param Indices $indices
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        Config $attributesConfig,
        Indices $indices,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();

        $this->attributesConfig = $attributesConfig;
        $this->indices = $indices;

        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @inheritDoc
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        foreach ($this->getCollection() as $searchIndex) {
            $storeId = $searchIndex->getStoreId();
            $indexData = [
                'entity_id' => $searchIndex->getEntityId(),
                'items_catalog_id' => $searchIndex->getItemsCatalogId()
            ];

            $indexData['index_id'] = $searchIndex->getIndexId();

            $index = $this->indices->getIndex($storeId, $searchIndex->getIndexId());

            $searchable = $index->getSearchableAttributes();
            $indexData['searchable'] = array_merge(
                $searchable->getFullText() ?? [],
                $searchable->getFullTextBoosted() ?? [],
                $searchable->getFullTextStronglyBoosted() ?? []
            );
            $indexData['filterable'] = $index->getFacetableAttributes()->getText();
            $indexData['sortable'] = $index->getSortableAttributes()->getText();

            $this->loadedData[$searchIndex->getEntityId()] = $indexData;
        }

        return $this->loadedData;
    }
}
