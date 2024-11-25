<?php
/**
 * SearchConfigSchema
 *
 * PHP version 7.4
 *
 * @category Class
 * @package  Synerise\ItemsSearchConfigApiClient
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 */

/**
 * Synerise search configuration API
 *
 * Synerise search configuration API v2.0 documentation
 *
 * The version of the OpenAPI document: v2
 * Generated by: https://openapi-generator.tech
 * Generator version: 7.8.0-SNAPSHOT
 */

/**
 * NOTE: This class is auto generated by OpenAPI Generator (https://openapi-generator.tech).
 * https://openapi-generator.tech
 * Do not edit the class manually.
 */

namespace Synerise\ItemsSearchConfigApiClient\Model;

use \ArrayAccess;
use \Synerise\ItemsSearchConfigApiClient\ObjectSerializer;

/**
 * SearchConfigSchema Class Doc Comment
 *
 * @category Class
 * @description Details of a single index
 * @package  Synerise\ItemsSearchConfigApiClient
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 * @implements \ArrayAccess<string, mixed>
 */
class SearchConfigSchema implements ModelInterface, ArrayAccess, \JsonSerializable
{
    public const DISCRIMINATOR = null;

    /**
      * The original name of the model.
      *
      * @var string
      */
    protected static $openAPIModelName = 'SearchConfigSchema';

    /**
      * Array of property to type mappings. Used for (de)serialization
      *
      * @var string[]
      */
    protected static $openAPITypes = [
        'index_id' => 'string',
        'index_name' => 'string',
        'description' => 'string',
        'items_catalog_id' => 'string',
        'language' => 'string',
        'enabled' => 'bool',
        'ignore_unavailable_items' => 'bool',
        'scoring' => '\Synerise\ItemsSearchConfigApiClient\Model\ScoringSchema',
        'suggestions' => '\Synerise\ItemsSearchConfigApiClient\Model\Suggestions',
        'tokenizer' => '\Synerise\ItemsSearchConfigApiClient\Model\Tokenizer',
        'analyzers' => '\Synerise\ItemsSearchConfigApiClient\Model\Analyzers',
        'attributes_without_prefix_search' => 'string[]',
        'attributes_without_typo_tolerance' => 'string[]',
        'values_without_typo_tolerance' => 'string[]',
        'typo_tolerance_on_numeric_values' => 'bool',
        'searchable_attributes' => '\Synerise\ItemsSearchConfigApiClient\Model\SearchableAttributesSchema',
        'displayable_attributes' => 'string[]',
        'facetable_attributes' => '\Synerise\ItemsSearchConfigApiClient\Model\FacetableAttributesSchema',
        'filterable_attributes' => '\Synerise\ItemsSearchConfigApiClient\Model\FilterableAttributesSchema',
        'sortable_attributes' => '\Synerise\ItemsSearchConfigApiClient\Model\SortableAttributesSchema',
        'distinct_filter_attributes' => '\Synerise\ItemsSearchConfigApiClient\Model\DistinctFilterAttributesSchema',
        'recent_searches' => '\Synerise\ItemsSearchConfigApiClient\Model\RecentSearchesConfig',
        'updated_at' => '\DateTime',
        'created_at' => '\DateTime'
    ];

    /**
      * Array of property to format mappings. Used for (de)serialization
      *
      * @var string[]
      * @phpstan-var array<string, string|null>
      * @psalm-var array<string, string|null>
      */
    protected static $openAPIFormats = [
        'index_id' => null,
        'index_name' => null,
        'description' => null,
        'items_catalog_id' => null,
        'language' => null,
        'enabled' => null,
        'ignore_unavailable_items' => null,
        'scoring' => null,
        'suggestions' => null,
        'tokenizer' => null,
        'analyzers' => null,
        'attributes_without_prefix_search' => null,
        'attributes_without_typo_tolerance' => null,
        'values_without_typo_tolerance' => null,
        'typo_tolerance_on_numeric_values' => null,
        'searchable_attributes' => null,
        'displayable_attributes' => null,
        'facetable_attributes' => null,
        'filterable_attributes' => null,
        'sortable_attributes' => null,
        'distinct_filter_attributes' => null,
        'recent_searches' => null,
        'updated_at' => 'date-time',
        'created_at' => 'date-time'
    ];

    /**
      * Array of nullable properties. Used for (de)serialization
      *
      * @var boolean[]
      */
    protected static array $openAPINullables = [
        'index_id' => false,
        'index_name' => false,
        'description' => false,
        'items_catalog_id' => false,
        'language' => false,
        'enabled' => false,
        'ignore_unavailable_items' => false,
        'scoring' => false,
        'suggestions' => false,
        'tokenizer' => false,
        'analyzers' => false,
        'attributes_without_prefix_search' => false,
        'attributes_without_typo_tolerance' => false,
        'values_without_typo_tolerance' => false,
        'typo_tolerance_on_numeric_values' => false,
        'searchable_attributes' => false,
        'displayable_attributes' => false,
        'facetable_attributes' => false,
        'filterable_attributes' => false,
        'sortable_attributes' => false,
        'distinct_filter_attributes' => false,
        'recent_searches' => false,
        'updated_at' => false,
        'created_at' => false
    ];

    /**
      * If a nullable field gets set to null, insert it here
      *
      * @var boolean[]
      */
    protected array $openAPINullablesSetToNull = [];

    /**
     * Array of property to type mappings. Used for (de)serialization
     *
     * @return array
     */
    public static function openAPITypes()
    {
        return self::$openAPITypes;
    }

    /**
     * Array of property to format mappings. Used for (de)serialization
     *
     * @return array
     */
    public static function openAPIFormats()
    {
        return self::$openAPIFormats;
    }

    /**
     * Array of nullable properties
     *
     * @return array
     */
    protected static function openAPINullables(): array
    {
        return self::$openAPINullables;
    }

    /**
     * Array of nullable field names deliberately set to null
     *
     * @return boolean[]
     */
    private function getOpenAPINullablesSetToNull(): array
    {
        return $this->openAPINullablesSetToNull;
    }

    /**
     * Setter - Array of nullable field names deliberately set to null
     *
     * @param boolean[] $openAPINullablesSetToNull
     */
    private function setOpenAPINullablesSetToNull(array $openAPINullablesSetToNull): void
    {
        $this->openAPINullablesSetToNull = $openAPINullablesSetToNull;
    }

    /**
     * Checks if a property is nullable
     *
     * @param string $property
     * @return bool
     */
    public static function isNullable(string $property): bool
    {
        return self::openAPINullables()[$property] ?? false;
    }

    /**
     * Checks if a nullable property is set to null.
     *
     * @param string $property
     * @return bool
     */
    public function isNullableSetToNull(string $property): bool
    {
        return in_array($property, $this->getOpenAPINullablesSetToNull(), true);
    }

    /**
     * Array of attributes where the key is the local name,
     * and the value is the original name
     *
     * @var string[]
     */
    protected static $attributeMap = [
        'index_id' => 'indexId',
        'index_name' => 'indexName',
        'description' => 'description',
        'items_catalog_id' => 'itemsCatalogId',
        'language' => 'language',
        'enabled' => 'enabled',
        'ignore_unavailable_items' => 'ignoreUnavailableItems',
        'scoring' => 'scoring',
        'suggestions' => 'suggestions',
        'tokenizer' => 'tokenizer',
        'analyzers' => 'analyzers',
        'attributes_without_prefix_search' => 'attributesWithoutPrefixSearch',
        'attributes_without_typo_tolerance' => 'attributesWithoutTypoTolerance',
        'values_without_typo_tolerance' => 'valuesWithoutTypoTolerance',
        'typo_tolerance_on_numeric_values' => 'typoToleranceOnNumericValues',
        'searchable_attributes' => 'searchableAttributes',
        'displayable_attributes' => 'displayableAttributes',
        'facetable_attributes' => 'facetableAttributes',
        'filterable_attributes' => 'filterableAttributes',
        'sortable_attributes' => 'sortableAttributes',
        'distinct_filter_attributes' => 'distinctFilterAttributes',
        'recent_searches' => 'recentSearches',
        'updated_at' => 'updatedAt',
        'created_at' => 'createdAt'
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'index_id' => 'setIndexId',
        'index_name' => 'setIndexName',
        'description' => 'setDescription',
        'items_catalog_id' => 'setItemsCatalogId',
        'language' => 'setLanguage',
        'enabled' => 'setEnabled',
        'ignore_unavailable_items' => 'setIgnoreUnavailableItems',
        'scoring' => 'setScoring',
        'suggestions' => 'setSuggestions',
        'tokenizer' => 'setTokenizer',
        'analyzers' => 'setAnalyzers',
        'attributes_without_prefix_search' => 'setAttributesWithoutPrefixSearch',
        'attributes_without_typo_tolerance' => 'setAttributesWithoutTypoTolerance',
        'values_without_typo_tolerance' => 'setValuesWithoutTypoTolerance',
        'typo_tolerance_on_numeric_values' => 'setTypoToleranceOnNumericValues',
        'searchable_attributes' => 'setSearchableAttributes',
        'displayable_attributes' => 'setDisplayableAttributes',
        'facetable_attributes' => 'setFacetableAttributes',
        'filterable_attributes' => 'setFilterableAttributes',
        'sortable_attributes' => 'setSortableAttributes',
        'distinct_filter_attributes' => 'setDistinctFilterAttributes',
        'recent_searches' => 'setRecentSearches',
        'updated_at' => 'setUpdatedAt',
        'created_at' => 'setCreatedAt'
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'index_id' => 'getIndexId',
        'index_name' => 'getIndexName',
        'description' => 'getDescription',
        'items_catalog_id' => 'getItemsCatalogId',
        'language' => 'getLanguage',
        'enabled' => 'getEnabled',
        'ignore_unavailable_items' => 'getIgnoreUnavailableItems',
        'scoring' => 'getScoring',
        'suggestions' => 'getSuggestions',
        'tokenizer' => 'getTokenizer',
        'analyzers' => 'getAnalyzers',
        'attributes_without_prefix_search' => 'getAttributesWithoutPrefixSearch',
        'attributes_without_typo_tolerance' => 'getAttributesWithoutTypoTolerance',
        'values_without_typo_tolerance' => 'getValuesWithoutTypoTolerance',
        'typo_tolerance_on_numeric_values' => 'getTypoToleranceOnNumericValues',
        'searchable_attributes' => 'getSearchableAttributes',
        'displayable_attributes' => 'getDisplayableAttributes',
        'facetable_attributes' => 'getFacetableAttributes',
        'filterable_attributes' => 'getFilterableAttributes',
        'sortable_attributes' => 'getSortableAttributes',
        'distinct_filter_attributes' => 'getDistinctFilterAttributes',
        'recent_searches' => 'getRecentSearches',
        'updated_at' => 'getUpdatedAt',
        'created_at' => 'getCreatedAt'
    ];

    /**
     * Array of attributes where the key is the local name,
     * and the value is the original name
     *
     * @return array
     */
    public static function attributeMap()
    {
        return self::$attributeMap;
    }

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @return array
     */
    public static function setters()
    {
        return self::$setters;
    }

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @return array
     */
    public static function getters()
    {
        return self::$getters;
    }

    /**
     * The original name of the model.
     *
     * @return string
     */
    public function getModelName()
    {
        return self::$openAPIModelName;
    }


    /**
     * Associative array for storing property values
     *
     * @var mixed[]
     */
    protected $container = [];

    /**
     * Constructor
     *
     * @param mixed[] $data Associated array of property values
     *                      initializing the model
     */
    public function __construct(array $data = null)
    {
        $this->setIfExists('index_id', $data ?? [], null);
        $this->setIfExists('index_name', $data ?? [], null);
        $this->setIfExists('description', $data ?? [], null);
        $this->setIfExists('items_catalog_id', $data ?? [], null);
        $this->setIfExists('language', $data ?? [], null);
        $this->setIfExists('enabled', $data ?? [], null);
        $this->setIfExists('ignore_unavailable_items', $data ?? [], true);
        $this->setIfExists('scoring', $data ?? [], null);
        $this->setIfExists('suggestions', $data ?? [], null);
        $this->setIfExists('tokenizer', $data ?? [], null);
        $this->setIfExists('analyzers', $data ?? [], null);
        $this->setIfExists('attributes_without_prefix_search', $data ?? [], null);
        $this->setIfExists('attributes_without_typo_tolerance', $data ?? [], null);
        $this->setIfExists('values_without_typo_tolerance', $data ?? [], null);
        $this->setIfExists('typo_tolerance_on_numeric_values', $data ?? [], true);
        $this->setIfExists('searchable_attributes', $data ?? [], null);
        $this->setIfExists('displayable_attributes', $data ?? [], null);
        $this->setIfExists('facetable_attributes', $data ?? [], null);
        $this->setIfExists('filterable_attributes', $data ?? [], null);
        $this->setIfExists('sortable_attributes', $data ?? [], null);
        $this->setIfExists('distinct_filter_attributes', $data ?? [], null);
        $this->setIfExists('recent_searches', $data ?? [], null);
        $this->setIfExists('updated_at', $data ?? [], null);
        $this->setIfExists('created_at', $data ?? [], null);
    }

    /**
    * Sets $this->container[$variableName] to the given data or to the given default Value; if $variableName
    * is nullable and its value is set to null in the $fields array, then mark it as "set to null" in the
    * $this->openAPINullablesSetToNull array
    *
    * @param string $variableName
    * @param array  $fields
    * @param mixed  $defaultValue
    */
    private function setIfExists(string $variableName, array $fields, $defaultValue): void
    {
        if (self::isNullable($variableName) && array_key_exists($variableName, $fields) && is_null($fields[$variableName])) {
            $this->openAPINullablesSetToNull[] = $variableName;
        }

        $this->container[$variableName] = $fields[$variableName] ?? $defaultValue;
    }

    /**
     * Show all the invalid properties with reasons.
     *
     * @return array invalid properties with reasons
     */
    public function listInvalidProperties()
    {
        $invalidProperties = [];

        return $invalidProperties;
    }

    /**
     * Validate all the properties in the model
     * return true if all passed
     *
     * @return bool True if all properties are valid
     */
    public function valid()
    {
        return count($this->listInvalidProperties()) === 0;
    }


    /**
     * Gets index_id
     *
     * @return string|null
     */
    public function getIndexId()
    {
        return $this->container['index_id'];
    }

    /**
     * Sets index_id
     *
     * @param string|null $index_id ID of the index
     *
     * @return self
     */
    public function setIndexId($index_id)
    {
        if (is_null($index_id)) {
            throw new \InvalidArgumentException('non-nullable index_id cannot be null');
        }
        $this->container['index_id'] = $index_id;

        return $this;
    }

    /**
     * Gets index_name
     *
     * @return string|null
     */
    public function getIndexName()
    {
        return $this->container['index_name'];
    }

    /**
     * Sets index_name
     *
     * @param string|null $index_name Human-friendly name of the index
     *
     * @return self
     */
    public function setIndexName($index_name)
    {
        if (is_null($index_name)) {
            throw new \InvalidArgumentException('non-nullable index_name cannot be null');
        }
        $this->container['index_name'] = $index_name;

        return $this;
    }

    /**
     * Gets description
     *
     * @return string|null
     */
    public function getDescription()
    {
        return $this->container['description'];
    }

    /**
     * Sets description
     *
     * @param string|null $description Description of the index
     *
     * @return self
     */
    public function setDescription($description)
    {
        if (is_null($description)) {
            throw new \InvalidArgumentException('non-nullable description cannot be null');
        }
        $this->container['description'] = $description;

        return $this;
    }

    /**
     * Gets items_catalog_id
     *
     * @return string|null
     */
    public function getItemsCatalogId()
    {
        return $this->container['items_catalog_id'];
    }

    /**
     * Sets items_catalog_id
     *
     * @param string|null $items_catalog_id ID of the item catalog from which the index will be created
     *
     * @return self
     */
    public function setItemsCatalogId($items_catalog_id)
    {
        if (is_null($items_catalog_id)) {
            throw new \InvalidArgumentException('non-nullable items_catalog_id cannot be null');
        }
        $this->container['items_catalog_id'] = $items_catalog_id;

        return $this;
    }

    /**
     * Gets language
     *
     * @return string|null
     */
    public function getLanguage()
    {
        return $this->container['language'];
    }

    /**
     * Sets language
     *
     * @param string|null $language Search language as ISO 639-1 code
     *
     * @return self
     */
    public function setLanguage($language)
    {
        if (is_null($language)) {
            throw new \InvalidArgumentException('non-nullable language cannot be null');
        }
        $this->container['language'] = $language;

        return $this;
    }

    /**
     * Gets enabled
     *
     * @return bool|null
     */
    public function getEnabled()
    {
        return $this->container['enabled'];
    }

    /**
     * Sets enabled
     *
     * @param bool|null $enabled When `true`, the index is enabled and can be queried.
     *
     * @return self
     */
    public function setEnabled($enabled)
    {
        if (is_null($enabled)) {
            throw new \InvalidArgumentException('non-nullable enabled cannot be null');
        }
        $this->container['enabled'] = $enabled;

        return $this;
    }

    /**
     * Gets ignore_unavailable_items
     *
     * @return bool|null
     */
    public function getIgnoreUnavailableItems()
    {
        return $this->container['ignore_unavailable_items'];
    }

    /**
     * Sets ignore_unavailable_items
     *
     * @param bool|null $ignore_unavailable_items When `true`, unavailable items are not indexed, which makes the search run faster.
     *
     * @return self
     */
    public function setIgnoreUnavailableItems($ignore_unavailable_items)
    {
        if (is_null($ignore_unavailable_items)) {
            throw new \InvalidArgumentException('non-nullable ignore_unavailable_items cannot be null');
        }
        $this->container['ignore_unavailable_items'] = $ignore_unavailable_items;

        return $this;
    }

    /**
     * Gets scoring
     *
     * @return \Synerise\ItemsSearchConfigApiClient\Model\ScoringSchema|null
     */
    public function getScoring()
    {
        return $this->container['scoring'];
    }

    /**
     * Sets scoring
     *
     * @param \Synerise\ItemsSearchConfigApiClient\Model\ScoringSchema|null $scoring scoring
     *
     * @return self
     */
    public function setScoring($scoring)
    {
        if (is_null($scoring)) {
            throw new \InvalidArgumentException('non-nullable scoring cannot be null');
        }
        $this->container['scoring'] = $scoring;

        return $this;
    }

    /**
     * Gets suggestions
     *
     * @return \Synerise\ItemsSearchConfigApiClient\Model\Suggestions|null
     */
    public function getSuggestions()
    {
        return $this->container['suggestions'];
    }

    /**
     * Sets suggestions
     *
     * @param \Synerise\ItemsSearchConfigApiClient\Model\Suggestions|null $suggestions suggestions
     *
     * @return self
     */
    public function setSuggestions($suggestions)
    {
        if (is_null($suggestions)) {
            throw new \InvalidArgumentException('non-nullable suggestions cannot be null');
        }
        $this->container['suggestions'] = $suggestions;

        return $this;
    }

    /**
     * Gets tokenizer
     *
     * @return \Synerise\ItemsSearchConfigApiClient\Model\Tokenizer|null
     */
    public function getTokenizer()
    {
        return $this->container['tokenizer'];
    }

    /**
     * Sets tokenizer
     *
     * @param \Synerise\ItemsSearchConfigApiClient\Model\Tokenizer|null $tokenizer tokenizer
     *
     * @return self
     */
    public function setTokenizer($tokenizer)
    {
        if (is_null($tokenizer)) {
            throw new \InvalidArgumentException('non-nullable tokenizer cannot be null');
        }
        $this->container['tokenizer'] = $tokenizer;

        return $this;
    }

    /**
     * Gets analyzers
     *
     * @return \Synerise\ItemsSearchConfigApiClient\Model\Analyzers|null
     */
    public function getAnalyzers()
    {
        return $this->container['analyzers'];
    }

    /**
     * Sets analyzers
     *
     * @param \Synerise\ItemsSearchConfigApiClient\Model\Analyzers|null $analyzers analyzers
     *
     * @return self
     */
    public function setAnalyzers($analyzers)
    {
        if (is_null($analyzers)) {
            throw new \InvalidArgumentException('non-nullable analyzers cannot be null');
        }
        $this->container['analyzers'] = $analyzers;

        return $this;
    }

    /**
     * Gets attributes_without_prefix_search
     *
     * @return string[]|null
     */
    public function getAttributesWithoutPrefixSearch()
    {
        return $this->container['attributes_without_prefix_search'];
    }

    /**
     * Sets attributes_without_prefix_search
     *
     * @param string[]|null $attributes_without_prefix_search Searchable attributes which will not be used in a prefix search
     *
     * @return self
     */
    public function setAttributesWithoutPrefixSearch($attributes_without_prefix_search)
    {
        if (is_null($attributes_without_prefix_search)) {
            throw new \InvalidArgumentException('non-nullable attributes_without_prefix_search cannot be null');
        }
        $this->container['attributes_without_prefix_search'] = $attributes_without_prefix_search;

        return $this;
    }

    /**
     * Gets attributes_without_typo_tolerance
     *
     * @return string[]|null
     */
    public function getAttributesWithoutTypoTolerance()
    {
        return $this->container['attributes_without_typo_tolerance'];
    }

    /**
     * Sets attributes_without_typo_tolerance
     *
     * @param string[]|null $attributes_without_typo_tolerance Searchable attributes for which typo tolerance is off
     *
     * @return self
     */
    public function setAttributesWithoutTypoTolerance($attributes_without_typo_tolerance)
    {
        if (is_null($attributes_without_typo_tolerance)) {
            throw new \InvalidArgumentException('non-nullable attributes_without_typo_tolerance cannot be null');
        }
        $this->container['attributes_without_typo_tolerance'] = $attributes_without_typo_tolerance;

        return $this;
    }

    /**
     * Gets values_without_typo_tolerance
     *
     * @return string[]|null
     */
    public function getValuesWithoutTypoTolerance()
    {
        return $this->container['values_without_typo_tolerance'];
    }

    /**
     * Sets values_without_typo_tolerance
     *
     * @param string[]|null $values_without_typo_tolerance Attributes values for which typo tolerance is off
     *
     * @return self
     */
    public function setValuesWithoutTypoTolerance($values_without_typo_tolerance)
    {
        if (is_null($values_without_typo_tolerance)) {
            throw new \InvalidArgumentException('non-nullable values_without_typo_tolerance cannot be null');
        }
        $this->container['values_without_typo_tolerance'] = $values_without_typo_tolerance;

        return $this;
    }

    /**
     * Gets typo_tolerance_on_numeric_values
     *
     * @return bool|null
     */
    public function getTypoToleranceOnNumericValues()
    {
        return $this->container['typo_tolerance_on_numeric_values'];
    }

    /**
     * Sets typo_tolerance_on_numeric_values
     *
     * @param bool|null $typo_tolerance_on_numeric_values When `true`, typo tolerance is active on numbers
     *
     * @return self
     */
    public function setTypoToleranceOnNumericValues($typo_tolerance_on_numeric_values)
    {
        if (is_null($typo_tolerance_on_numeric_values)) {
            throw new \InvalidArgumentException('non-nullable typo_tolerance_on_numeric_values cannot be null');
        }
        $this->container['typo_tolerance_on_numeric_values'] = $typo_tolerance_on_numeric_values;

        return $this;
    }

    /**
     * Gets searchable_attributes
     *
     * @return \Synerise\ItemsSearchConfigApiClient\Model\SearchableAttributesSchema|null
     */
    public function getSearchableAttributes()
    {
        return $this->container['searchable_attributes'];
    }

    /**
     * Sets searchable_attributes
     *
     * @param \Synerise\ItemsSearchConfigApiClient\Model\SearchableAttributesSchema|null $searchable_attributes searchable_attributes
     *
     * @return self
     */
    public function setSearchableAttributes($searchable_attributes)
    {
        if (is_null($searchable_attributes)) {
            throw new \InvalidArgumentException('non-nullable searchable_attributes cannot be null');
        }
        $this->container['searchable_attributes'] = $searchable_attributes;

        return $this;
    }

    /**
     * Gets displayable_attributes
     *
     * @return string[]|null
     */
    public function getDisplayableAttributes()
    {
        return $this->container['displayable_attributes'];
    }

    /**
     * Sets displayable_attributes
     *
     * @param string[]|null $displayable_attributes Attributes shown in the search results
     *
     * @return self
     */
    public function setDisplayableAttributes($displayable_attributes)
    {
        if (is_null($displayable_attributes)) {
            throw new \InvalidArgumentException('non-nullable displayable_attributes cannot be null');
        }
        $this->container['displayable_attributes'] = $displayable_attributes;

        return $this;
    }

    /**
     * Gets facetable_attributes
     *
     * @return \Synerise\ItemsSearchConfigApiClient\Model\FacetableAttributesSchema|null
     */
    public function getFacetableAttributes()
    {
        return $this->container['facetable_attributes'];
    }

    /**
     * Sets facetable_attributes
     *
     * @param \Synerise\ItemsSearchConfigApiClient\Model\FacetableAttributesSchema|null $facetable_attributes facetable_attributes
     *
     * @return self
     */
    public function setFacetableAttributes($facetable_attributes)
    {
        if (is_null($facetable_attributes)) {
            throw new \InvalidArgumentException('non-nullable facetable_attributes cannot be null');
        }
        $this->container['facetable_attributes'] = $facetable_attributes;

        return $this;
    }

    /**
     * Gets filterable_attributes
     *
     * @return \Synerise\ItemsSearchConfigApiClient\Model\FilterableAttributesSchema|null
     */
    public function getFilterableAttributes()
    {
        return $this->container['filterable_attributes'];
    }

    /**
     * Sets filterable_attributes
     *
     * @param \Synerise\ItemsSearchConfigApiClient\Model\FilterableAttributesSchema|null $filterable_attributes filterable_attributes
     *
     * @return self
     */
    public function setFilterableAttributes($filterable_attributes)
    {
        if (is_null($filterable_attributes)) {
            throw new \InvalidArgumentException('non-nullable filterable_attributes cannot be null');
        }
        $this->container['filterable_attributes'] = $filterable_attributes;

        return $this;
    }

    /**
     * Gets sortable_attributes
     *
     * @return \Synerise\ItemsSearchConfigApiClient\Model\SortableAttributesSchema|null
     */
    public function getSortableAttributes()
    {
        return $this->container['sortable_attributes'];
    }

    /**
     * Sets sortable_attributes
     *
     * @param \Synerise\ItemsSearchConfigApiClient\Model\SortableAttributesSchema|null $sortable_attributes sortable_attributes
     *
     * @return self
     */
    public function setSortableAttributes($sortable_attributes)
    {
        if (is_null($sortable_attributes)) {
            throw new \InvalidArgumentException('non-nullable sortable_attributes cannot be null');
        }
        $this->container['sortable_attributes'] = $sortable_attributes;

        return $this;
    }

    /**
     * Gets distinct_filter_attributes
     *
     * @return \Synerise\ItemsSearchConfigApiClient\Model\DistinctFilterAttributesSchema|null
     */
    public function getDistinctFilterAttributes()
    {
        return $this->container['distinct_filter_attributes'];
    }

    /**
     * Sets distinct_filter_attributes
     *
     * @param \Synerise\ItemsSearchConfigApiClient\Model\DistinctFilterAttributesSchema|null $distinct_filter_attributes distinct_filter_attributes
     *
     * @return self
     */
    public function setDistinctFilterAttributes($distinct_filter_attributes)
    {
        if (is_null($distinct_filter_attributes)) {
            throw new \InvalidArgumentException('non-nullable distinct_filter_attributes cannot be null');
        }
        $this->container['distinct_filter_attributes'] = $distinct_filter_attributes;

        return $this;
    }

    /**
     * Gets recent_searches
     *
     * @return \Synerise\ItemsSearchConfigApiClient\Model\RecentSearchesConfig|null
     */
    public function getRecentSearches()
    {
        return $this->container['recent_searches'];
    }

    /**
     * Sets recent_searches
     *
     * @param \Synerise\ItemsSearchConfigApiClient\Model\RecentSearchesConfig|null $recent_searches recent_searches
     *
     * @return self
     */
    public function setRecentSearches($recent_searches)
    {
        if (is_null($recent_searches)) {
            throw new \InvalidArgumentException('non-nullable recent_searches cannot be null');
        }
        $this->container['recent_searches'] = $recent_searches;

        return $this;
    }

    /**
     * Gets updated_at
     *
     * @return \DateTime|null
     */
    public function getUpdatedAt()
    {
        return $this->container['updated_at'];
    }

    /**
     * Sets updated_at
     *
     * @param \DateTime|null $updated_at Last update time in YYYY-MM-DDThh:mm:ssZ format (ISO 8601, UTC)
     *
     * @return self
     */
    public function setUpdatedAt($updated_at)
    {
        if (is_null($updated_at)) {
            throw new \InvalidArgumentException('non-nullable updated_at cannot be null');
        }
        $this->container['updated_at'] = $updated_at;

        return $this;
    }

    /**
     * Gets created_at
     *
     * @return \DateTime|null
     */
    public function getCreatedAt()
    {
        return $this->container['created_at'];
    }

    /**
     * Sets created_at
     *
     * @param \DateTime|null $created_at Creation time in YYYY-MM-DDThh:mm:ssZ format (ISO 8601, UTC)
     *
     * @return self
     */
    public function setCreatedAt($created_at)
    {
        if (is_null($created_at)) {
            throw new \InvalidArgumentException('non-nullable created_at cannot be null');
        }
        $this->container['created_at'] = $created_at;

        return $this;
    }
    /**
     * Returns true if offset exists. False otherwise.
     *
     * @param integer $offset Offset
     *
     * @return boolean
     */
    public function offsetExists($offset): bool
    {
        return isset($this->container[$offset]);
    }

    /**
     * Gets offset.
     *
     * @param integer $offset Offset
     *
     * @return mixed|null
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->container[$offset] ?? null;
    }

    /**
     * Sets value based on offset.
     *
     * @param int|null $offset Offset
     * @param mixed    $value  Value to be set
     *
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    /**
     * Unsets offset.
     *
     * @param integer $offset Offset
     *
     * @return void
     */
    public function offsetUnset($offset): void
    {
        unset($this->container[$offset]);
    }

    /**
     * Serializes the object to a value that can be serialized natively by json_encode().
     * @link https://www.php.net/manual/en/jsonserializable.jsonserialize.php
     *
     * @return mixed Returns data which can be serialized by json_encode(), which is a value
     * of any type other than a resource.
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
       return ObjectSerializer::sanitizeForSerialization($this);
    }

    /**
     * Gets the string presentation of the object
     *
     * @return string
     */
    public function __toString()
    {
        return json_encode(
            ObjectSerializer::sanitizeForSerialization($this),
            JSON_PRETTY_PRINT
        );
    }

    /**
     * Gets a header-safe presentation of the object
     *
     * @return string
     */
    public function toHeaderValue()
    {
        return json_encode(ObjectSerializer::sanitizeForSerialization($this));
    }
}


