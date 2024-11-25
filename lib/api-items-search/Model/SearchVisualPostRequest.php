<?php
/**
 * SearchVisualPostRequest
 *
 * PHP version 7.4
 *
 * @category Class
 * @package  Synerise\ItemsSearchApiClient
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 */

/**
 * Synerise search API
 *
 * Synerise search API v2.0 documentation
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

namespace Synerise\ItemsSearchApiClient\Model;

use \ArrayAccess;
use \Synerise\ItemsSearchApiClient\ObjectSerializer;

/**
 * SearchVisualPostRequest Class Doc Comment
 *
 * @category Class
 * @package  Synerise\ItemsSearchApiClient
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 * @implements \ArrayAccess<string, mixed>
 */
class SearchVisualPostRequest implements ModelInterface, ArrayAccess, \JsonSerializable
{
    public const DISCRIMINATOR = null;

    /**
      * The original name of the model.
      *
      * @var string
      */
    protected static $openAPIModelName = 'SearchVisualPost_request';

    /**
      * Array of property to type mappings. Used for (de)serialization
      *
      * @var string[]
      */
    protected static $openAPITypes = [
        'url' => 'string',
        'page' => 'int',
        'limit' => 'int',
        'sort_by' => 'string',
        'ordering' => '\Synerise\ItemsSearchApiClient\Model\PaginationOrdering',
        'include_meta' => 'bool',
        'client_uuid' => 'string',
        'personalize' => 'bool',
        'correlation_id' => 'string',
        'search_id' => 'string',
        'sort_by_metric' => '\Synerise\ItemsSearchApiClient\Model\SortByMetric',
        'sort_by_geo_points' => 'string',
        'filter_geo_points' => 'string[]',
        'filter_around_radius' => 'int',
        'filter_anchor' => 'string',
        'filters' => 'string',
        'facets' => 'string[]',
        'custom_filtered_facets' => 'array<string,string>',
        'facets_size' => 'int',
        'max_values_per_facet' => 'int',
        'case_sensitive_facet_values' => 'bool',
        'include_facets' => '\Synerise\ItemsSearchApiClient\Model\IncludeFacets',
        'context' => 'string[]',
        'display_attributes' => 'string[]',
        'ignore_query_rules' => 'bool',
        'exclude_query_rules' => 'int[]'
    ];

    /**
      * Array of property to format mappings. Used for (de)serialization
      *
      * @var string[]
      * @phpstan-var array<string, string|null>
      * @psalm-var array<string, string|null>
      */
    protected static $openAPIFormats = [
        'url' => null,
        'page' => 'int32',
        'limit' => 'int32',
        'sort_by' => null,
        'ordering' => null,
        'include_meta' => null,
        'client_uuid' => null,
        'personalize' => null,
        'correlation_id' => null,
        'search_id' => null,
        'sort_by_metric' => null,
        'sort_by_geo_points' => null,
        'filter_geo_points' => null,
        'filter_around_radius' => 'int32',
        'filter_anchor' => null,
        'filters' => null,
        'facets' => null,
        'custom_filtered_facets' => null,
        'facets_size' => null,
        'max_values_per_facet' => null,
        'case_sensitive_facet_values' => null,
        'include_facets' => null,
        'context' => null,
        'display_attributes' => null,
        'ignore_query_rules' => null,
        'exclude_query_rules' => null
    ];

    /**
      * Array of nullable properties. Used for (de)serialization
      *
      * @var boolean[]
      */
    protected static array $openAPINullables = [
        'url' => false,
        'page' => false,
        'limit' => false,
        'sort_by' => false,
        'ordering' => false,
        'include_meta' => false,
        'client_uuid' => false,
        'personalize' => false,
        'correlation_id' => false,
        'search_id' => false,
        'sort_by_metric' => false,
        'sort_by_geo_points' => false,
        'filter_geo_points' => false,
        'filter_around_radius' => false,
        'filter_anchor' => false,
        'filters' => false,
        'facets' => false,
        'custom_filtered_facets' => false,
        'facets_size' => false,
        'max_values_per_facet' => false,
        'case_sensitive_facet_values' => false,
        'include_facets' => false,
        'context' => false,
        'display_attributes' => false,
        'ignore_query_rules' => false,
        'exclude_query_rules' => false
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
        'url' => 'url',
        'page' => 'page',
        'limit' => 'limit',
        'sort_by' => 'sortBy',
        'ordering' => 'ordering',
        'include_meta' => 'includeMeta',
        'client_uuid' => 'clientUUID',
        'personalize' => 'personalize',
        'correlation_id' => 'correlationId',
        'search_id' => 'searchId',
        'sort_by_metric' => 'sortByMetric',
        'sort_by_geo_points' => 'sortByGeoPoints',
        'filter_geo_points' => 'filterGeoPoints',
        'filter_around_radius' => 'filterAroundRadius',
        'filter_anchor' => 'filterAnchor',
        'filters' => 'filters',
        'facets' => 'facets',
        'custom_filtered_facets' => 'customFilteredFacets',
        'facets_size' => 'facetsSize',
        'max_values_per_facet' => 'maxValuesPerFacet',
        'case_sensitive_facet_values' => 'caseSensitiveFacetValues',
        'include_facets' => 'includeFacets',
        'context' => 'context',
        'display_attributes' => 'displayAttributes',
        'ignore_query_rules' => 'ignoreQueryRules',
        'exclude_query_rules' => 'excludeQueryRules'
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'url' => 'setUrl',
        'page' => 'setPage',
        'limit' => 'setLimit',
        'sort_by' => 'setSortBy',
        'ordering' => 'setOrdering',
        'include_meta' => 'setIncludeMeta',
        'client_uuid' => 'setClientUuid',
        'personalize' => 'setPersonalize',
        'correlation_id' => 'setCorrelationId',
        'search_id' => 'setSearchId',
        'sort_by_metric' => 'setSortByMetric',
        'sort_by_geo_points' => 'setSortByGeoPoints',
        'filter_geo_points' => 'setFilterGeoPoints',
        'filter_around_radius' => 'setFilterAroundRadius',
        'filter_anchor' => 'setFilterAnchor',
        'filters' => 'setFilters',
        'facets' => 'setFacets',
        'custom_filtered_facets' => 'setCustomFilteredFacets',
        'facets_size' => 'setFacetsSize',
        'max_values_per_facet' => 'setMaxValuesPerFacet',
        'case_sensitive_facet_values' => 'setCaseSensitiveFacetValues',
        'include_facets' => 'setIncludeFacets',
        'context' => 'setContext',
        'display_attributes' => 'setDisplayAttributes',
        'ignore_query_rules' => 'setIgnoreQueryRules',
        'exclude_query_rules' => 'setExcludeQueryRules'
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'url' => 'getUrl',
        'page' => 'getPage',
        'limit' => 'getLimit',
        'sort_by' => 'getSortBy',
        'ordering' => 'getOrdering',
        'include_meta' => 'getIncludeMeta',
        'client_uuid' => 'getClientUuid',
        'personalize' => 'getPersonalize',
        'correlation_id' => 'getCorrelationId',
        'search_id' => 'getSearchId',
        'sort_by_metric' => 'getSortByMetric',
        'sort_by_geo_points' => 'getSortByGeoPoints',
        'filter_geo_points' => 'getFilterGeoPoints',
        'filter_around_radius' => 'getFilterAroundRadius',
        'filter_anchor' => 'getFilterAnchor',
        'filters' => 'getFilters',
        'facets' => 'getFacets',
        'custom_filtered_facets' => 'getCustomFilteredFacets',
        'facets_size' => 'getFacetsSize',
        'max_values_per_facet' => 'getMaxValuesPerFacet',
        'case_sensitive_facet_values' => 'getCaseSensitiveFacetValues',
        'include_facets' => 'getIncludeFacets',
        'context' => 'getContext',
        'display_attributes' => 'getDisplayAttributes',
        'ignore_query_rules' => 'getIgnoreQueryRules',
        'exclude_query_rules' => 'getExcludeQueryRules'
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
        $this->setIfExists('url', $data ?? [], null);
        $this->setIfExists('page', $data ?? [], null);
        $this->setIfExists('limit', $data ?? [], 20);
        $this->setIfExists('sort_by', $data ?? [], null);
        $this->setIfExists('ordering', $data ?? [], null);
        $this->setIfExists('include_meta', $data ?? [], null);
        $this->setIfExists('client_uuid', $data ?? [], null);
        $this->setIfExists('personalize', $data ?? [], true);
        $this->setIfExists('correlation_id', $data ?? [], null);
        $this->setIfExists('search_id', $data ?? [], null);
        $this->setIfExists('sort_by_metric', $data ?? [], null);
        $this->setIfExists('sort_by_geo_points', $data ?? [], null);
        $this->setIfExists('filter_geo_points', $data ?? [], null);
        $this->setIfExists('filter_around_radius', $data ?? [], 1000);
        $this->setIfExists('filter_anchor', $data ?? [], null);
        $this->setIfExists('filters', $data ?? [], null);
        $this->setIfExists('facets', $data ?? [], null);
        $this->setIfExists('custom_filtered_facets', $data ?? [], null);
        $this->setIfExists('facets_size', $data ?? [], 2000);
        $this->setIfExists('max_values_per_facet', $data ?? [], 50);
        $this->setIfExists('case_sensitive_facet_values', $data ?? [], false);
        $this->setIfExists('include_facets', $data ?? [], null);
        $this->setIfExists('context', $data ?? [], null);
        $this->setIfExists('display_attributes', $data ?? [], null);
        $this->setIfExists('ignore_query_rules', $data ?? [], false);
        $this->setIfExists('exclude_query_rules', $data ?? [], null);
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

        if ($this->container['url'] === null) {
            $invalidProperties[] = "'url' can't be null";
        }
        if (!is_null($this->container['limit']) && ($this->container['limit'] > 500)) {
            $invalidProperties[] = "invalid value for 'limit', must be smaller than or equal to 500.";
        }

        if (!is_null($this->container['limit']) && ($this->container['limit'] < 0)) {
            $invalidProperties[] = "invalid value for 'limit', must be bigger than or equal to 0.";
        }

        if (!is_null($this->container['facets_size']) && ($this->container['facets_size'] > 10000)) {
            $invalidProperties[] = "invalid value for 'facets_size', must be smaller than or equal to 10000.";
        }

        if (!is_null($this->container['facets_size']) && ($this->container['facets_size'] < 1)) {
            $invalidProperties[] = "invalid value for 'facets_size', must be bigger than or equal to 1.";
        }

        if (!is_null($this->container['max_values_per_facet']) && ($this->container['max_values_per_facet'] > 1000)) {
            $invalidProperties[] = "invalid value for 'max_values_per_facet', must be smaller than or equal to 1000.";
        }

        if (!is_null($this->container['max_values_per_facet']) && ($this->container['max_values_per_facet'] < 1)) {
            $invalidProperties[] = "invalid value for 'max_values_per_facet', must be bigger than or equal to 1.";
        }

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
     * Gets url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->container['url'];
    }

    /**
     * Sets url
     *
     * @param string $url URL of the image to be used in the visual search
     *
     * @return self
     */
    public function setUrl($url)
    {
        if (is_null($url)) {
            throw new \InvalidArgumentException('non-nullable url cannot be null');
        }
        $this->container['url'] = $url;

        return $this;
    }

    /**
     * Gets page
     *
     * @return int|null
     */
    public function getPage()
    {
        return $this->container['page'];
    }

    /**
     * Sets page
     *
     * @param int|null $page Page number to return for pagination. The first page has the index `1`.
     *
     * @return self
     */
    public function setPage($page)
    {
        if (is_null($page)) {
            throw new \InvalidArgumentException('non-nullable page cannot be null');
        }
        $this->container['page'] = $page;

        return $this;
    }

    /**
     * Gets limit
     *
     * @return int|null
     */
    public function getLimit()
    {
        return $this->container['limit'];
    }

    /**
     * Sets limit
     *
     * @param int|null $limit The number of items to return per page
     *
     * @return self
     */
    public function setLimit($limit)
    {
        if (is_null($limit)) {
            throw new \InvalidArgumentException('non-nullable limit cannot be null');
        }

        if (($limit > 500)) {
            throw new \InvalidArgumentException('invalid value for $limit when calling SearchVisualPostRequest., must be smaller than or equal to 500.');
        }
        if (($limit < 0)) {
            throw new \InvalidArgumentException('invalid value for $limit when calling SearchVisualPostRequest., must be bigger than or equal to 0.');
        }

        $this->container['limit'] = $limit;

        return $this;
    }

    /**
     * Gets sort_by
     *
     * @return string|null
     */
    public function getSortBy()
    {
        return $this->container['sort_by'];
    }

    /**
     * Sets sort_by
     *
     * @param string|null $sort_by Name of the attribute by which the data will be sorted.  Sorting by attribute may cause a promoted item to be in a different position that defined in a query rule.
     *
     * @return self
     */
    public function setSortBy($sort_by)
    {
        if (is_null($sort_by)) {
            throw new \InvalidArgumentException('non-nullable sort_by cannot be null');
        }
        $this->container['sort_by'] = $sort_by;

        return $this;
    }

    /**
     * Gets ordering
     *
     * @return \Synerise\ItemsSearchApiClient\Model\PaginationOrdering|null
     */
    public function getOrdering()
    {
        return $this->container['ordering'];
    }

    /**
     * Sets ordering
     *
     * @param \Synerise\ItemsSearchApiClient\Model\PaginationOrdering|null $ordering ordering
     *
     * @return self
     */
    public function setOrdering($ordering)
    {
        if (is_null($ordering)) {
            throw new \InvalidArgumentException('non-nullable ordering cannot be null');
        }
        $this->container['ordering'] = $ordering;

        return $this;
    }

    /**
     * Gets include_meta
     *
     * @return bool|null
     */
    public function getIncludeMeta()
    {
        return $this->container['include_meta'];
    }

    /**
     * Sets include_meta
     *
     * @param bool|null $include_meta When `true`, pagination metadata is included in the response body.  When `false`, the data is included in the response headers:  - Link: links to neighbors, first, and last pages in pagination.  - X-Pagination-Total-Count: total number of items on all pages  - X-Pagination-Total-Pages: total number of pages  - X-Pagination-Page: current page  - X-Pagination-Limit: maximum number of items on a page  - X-Pagination-Sorted-By: parameter that the items were sorted by  - X-Pagination-Ordering: sorting direction
     *
     * @return self
     */
    public function setIncludeMeta($include_meta)
    {
        if (is_null($include_meta)) {
            throw new \InvalidArgumentException('non-nullable include_meta cannot be null');
        }
        $this->container['include_meta'] = $include_meta;

        return $this;
    }

    /**
     * Gets client_uuid
     *
     * @return string|null
     */
    public function getClientUuid()
    {
        return $this->container['client_uuid'];
    }

    /**
     * Sets client_uuid
     *
     * @param string|null $client_uuid UUID of the profile for which the search is performed
     *
     * @return self
     */
    public function setClientUuid($client_uuid)
    {
        if (is_null($client_uuid)) {
            throw new \InvalidArgumentException('non-nullable client_uuid cannot be null');
        }
        $this->container['client_uuid'] = $client_uuid;

        return $this;
    }

    /**
     * Gets personalize
     *
     * @return bool|null
     */
    public function getPersonalize()
    {
        return $this->container['personalize'];
    }

    /**
     * Sets personalize
     *
     * @param bool|null $personalize If set to `false`, the search result is not personalized.
     *
     * @return self
     */
    public function setPersonalize($personalize)
    {
        if (is_null($personalize)) {
            throw new \InvalidArgumentException('non-nullable personalize cannot be null');
        }
        $this->container['personalize'] = $personalize;

        return $this;
    }

    /**
     * Gets correlation_id
     *
     * @return string|null
     */
    public function getCorrelationId()
    {
        return $this->container['correlation_id'];
    }

    /**
     * Sets correlation_id
     *
     * @param string|null $correlation_id Correlation ID for pagination. If a search with the ID was performed recently (last 10 minutes), the cached results will be used.  Do not send this if sortBy/filters/sorting order, etc. have changed - the cached results may have different order or may match different filters.
     *
     * @return self
     */
    public function setCorrelationId($correlation_id)
    {
        if (is_null($correlation_id)) {
            throw new \InvalidArgumentException('non-nullable correlation_id cannot be null');
        }
        $this->container['correlation_id'] = $correlation_id;

        return $this;
    }

    /**
     * Gets search_id
     *
     * @return string|null
     * @deprecated
     */
    public function getSearchId()
    {
        return $this->container['search_id'];
    }

    /**
     * Sets search_id
     *
     * @param string|null $search_id **DEPRECATED - use correlationId instead**  Search ID for pagination. If a search with the ID was performed recently (last 10 minutes), the cached results will be used.  Do not send this if sortBy/filters/sorting order, etc. have changed - the cached results may have different order or may match different filters.
     *
     * @return self
     * @deprecated
     */
    public function setSearchId($search_id)
    {
        if (is_null($search_id)) {
            throw new \InvalidArgumentException('non-nullable search_id cannot be null');
        }
        $this->container['search_id'] = $search_id;

        return $this;
    }

    /**
     * Gets sort_by_metric
     *
     * @return \Synerise\ItemsSearchApiClient\Model\SortByMetric|null
     */
    public function getSortByMetric()
    {
        return $this->container['sort_by_metric'];
    }

    /**
     * Sets sort_by_metric
     *
     * @param \Synerise\ItemsSearchApiClient\Model\SortByMetric|null $sort_by_metric sort_by_metric
     *
     * @return self
     */
    public function setSortByMetric($sort_by_metric)
    {
        if (is_null($sort_by_metric)) {
            throw new \InvalidArgumentException('non-nullable sort_by_metric cannot be null');
        }
        $this->container['sort_by_metric'] = $sort_by_metric;

        return $this;
    }

    /**
     * Gets sort_by_geo_points
     *
     * @return string|null
     */
    public function getSortByGeoPoints()
    {
        return $this->container['sort_by_geo_points'];
    }

    /**
     * Sets sort_by_geo_points
     *
     * @param string|null $sort_by_geo_points Geo-point (`{latitude},{longitude}`) for data sorting. Results are sorted by distance from this point. `ordering: asc` means \"closest first\".
     *
     * @return self
     */
    public function setSortByGeoPoints($sort_by_geo_points)
    {
        if (is_null($sort_by_geo_points)) {
            throw new \InvalidArgumentException('non-nullable sort_by_geo_points cannot be null');
        }
        $this->container['sort_by_geo_points'] = $sort_by_geo_points;

        return $this;
    }

    /**
     * Gets filter_geo_points
     *
     * @return string[]|null
     */
    public function getFilterGeoPoints()
    {
        return $this->container['filter_geo_points'];
    }

    /**
     * Sets filter_geo_points
     *
     * @param string[]|null $filter_geo_points The definition of a geographical area to filter by.  Given one geo-point, the results will be limited to a radius around a point. To override the default radius (1000 meters), provide the `filterAroundRadius` parameter. **Example input:** `[\"34.052235,-118.243685\"]`  Given two geo-points, the results will be limited to a rectangular area. The first point defines the upper-left corner of the rectangle and the second is the lower-right corner. **Example input:** `[\"50,-100\", \"25,150\"]`  Given three or more geo-points, the results will be limited to a polygonal area. **Example input:** `[\"50,0\", \"40,20\", \"-20,10\"]`
     *
     * @return self
     */
    public function setFilterGeoPoints($filter_geo_points)
    {
        if (is_null($filter_geo_points)) {
            throw new \InvalidArgumentException('non-nullable filter_geo_points cannot be null');
        }
        $this->container['filter_geo_points'] = $filter_geo_points;

        return $this;
    }

    /**
     * Gets filter_around_radius
     *
     * @return int|null
     */
    public function getFilterAroundRadius()
    {
        return $this->container['filter_around_radius'];
    }

    /**
     * Sets filter_around_radius
     *
     * @param int|null $filter_around_radius Radius in meters to be used when filtering using geo-location. Can only be used when filtering by a single geo-point.
     *
     * @return self
     */
    public function setFilterAroundRadius($filter_around_radius)
    {
        if (is_null($filter_around_radius)) {
            throw new \InvalidArgumentException('non-nullable filter_around_radius cannot be null');
        }
        $this->container['filter_around_radius'] = $filter_around_radius;

        return $this;
    }

    /**
     * Gets filter_anchor
     *
     * @return string|null
     */
    public function getFilterAnchor()
    {
        return $this->container['filter_anchor'];
    }

    /**
     * Sets filter_anchor
     *
     * @param string|null $filter_anchor Anchor (`{width},{height}`) by which the visual results data will be filtered. `{width},{height}` correspond to normalized image coordinates, i.e. they are in range [0,1]. Anchor (0,0) corresponds to the top-left pixel of an image.
     *
     * @return self
     */
    public function setFilterAnchor($filter_anchor)
    {
        if (is_null($filter_anchor)) {
            throw new \InvalidArgumentException('non-nullable filter_anchor cannot be null');
        }
        $this->container['filter_anchor'] = $filter_anchor;

        return $this;
    }

    /**
     * Gets filters
     *
     * @return string|null
     */
    public function getFilters()
    {
        return $this->container['filters'];
    }

    /**
     * Sets filters
     *
     * @param string|null $filters IQL query string. For details, see the [Help Center](https://help.synerise.com/developers/iql/).
     *
     * @return self
     */
    public function setFilters($filters)
    {
        if (is_null($filters)) {
            throw new \InvalidArgumentException('non-nullable filters cannot be null');
        }
        $this->container['filters'] = $filters;

        return $this;
    }

    /**
     * Gets facets
     *
     * @return string[]|null
     */
    public function getFacets()
    {
        return $this->container['facets'];
    }

    /**
     * Sets facets
     *
     * @param string[]|null $facets A list of attributes for which facets will be returned. A single `*` value matches all facetable attributes.  To determine which groups of facets should be returned, use the `includeFacets` parameter.
     *
     * @return self
     */
    public function setFacets($facets)
    {
        if (is_null($facets)) {
            throw new \InvalidArgumentException('non-nullable facets cannot be null');
        }
        $this->container['facets'] = $facets;

        return $this;
    }

    /**
     * Gets custom_filtered_facets
     *
     * @return array<string,string>|null
     */
    public function getCustomFilteredFacets()
    {
        return $this->container['custom_filtered_facets'];
    }

    /**
     * Sets custom_filtered_facets
     *
     * @param array<string,string>|null $custom_filtered_facets A key-value map that takes attributes as keys and IQL query strings as values.  For each key a facet is returned that includes only the items filtered by the provided IQL query string.
     *
     * @return self
     */
    public function setCustomFilteredFacets($custom_filtered_facets)
    {
        if (is_null($custom_filtered_facets)) {
            throw new \InvalidArgumentException('non-nullable custom_filtered_facets cannot be null');
        }
        $this->container['custom_filtered_facets'] = $custom_filtered_facets;

        return $this;
    }

    /**
     * Gets facets_size
     *
     * @return int|null
     */
    public function getFacetsSize()
    {
        return $this->container['facets_size'];
    }

    /**
     * Sets facets_size
     *
     * @param int|null $facets_size Determines how many items will be used for facets aggregation.
     *
     * @return self
     */
    public function setFacetsSize($facets_size)
    {
        if (is_null($facets_size)) {
            throw new \InvalidArgumentException('non-nullable facets_size cannot be null');
        }

        if (($facets_size > 10000)) {
            throw new \InvalidArgumentException('invalid value for $facets_size when calling SearchVisualPostRequest., must be smaller than or equal to 10000.');
        }
        if (($facets_size < 1)) {
            throw new \InvalidArgumentException('invalid value for $facets_size when calling SearchVisualPostRequest., must be bigger than or equal to 1.');
        }

        $this->container['facets_size'] = $facets_size;

        return $this;
    }

    /**
     * Gets max_values_per_facet
     *
     * @return int|null
     */
    public function getMaxValuesPerFacet()
    {
        return $this->container['max_values_per_facet'];
    }

    /**
     * Sets max_values_per_facet
     *
     * @param int|null $max_values_per_facet Determines how many values will be retrieved per facet.
     *
     * @return self
     */
    public function setMaxValuesPerFacet($max_values_per_facet)
    {
        if (is_null($max_values_per_facet)) {
            throw new \InvalidArgumentException('non-nullable max_values_per_facet cannot be null');
        }

        if (($max_values_per_facet > 1000)) {
            throw new \InvalidArgumentException('invalid value for $max_values_per_facet when calling SearchVisualPostRequest., must be smaller than or equal to 1000.');
        }
        if (($max_values_per_facet < 1)) {
            throw new \InvalidArgumentException('invalid value for $max_values_per_facet when calling SearchVisualPostRequest., must be bigger than or equal to 1.');
        }

        $this->container['max_values_per_facet'] = $max_values_per_facet;

        return $this;
    }

    /**
     * Gets case_sensitive_facet_values
     *
     * @return bool|null
     */
    public function getCaseSensitiveFacetValues()
    {
        return $this->container['case_sensitive_facet_values'];
    }

    /**
     * Sets case_sensitive_facet_values
     *
     * @param bool|null $case_sensitive_facet_values Specifies whether facets aggregation should be case sensitive.
     *
     * @return self
     */
    public function setCaseSensitiveFacetValues($case_sensitive_facet_values)
    {
        if (is_null($case_sensitive_facet_values)) {
            throw new \InvalidArgumentException('non-nullable case_sensitive_facet_values cannot be null');
        }
        $this->container['case_sensitive_facet_values'] = $case_sensitive_facet_values;

        return $this;
    }

    /**
     * Gets include_facets
     *
     * @return \Synerise\ItemsSearchApiClient\Model\IncludeFacets|null
     */
    public function getIncludeFacets()
    {
        return $this->container['include_facets'];
    }

    /**
     * Sets include_facets
     *
     * @param \Synerise\ItemsSearchApiClient\Model\IncludeFacets|null $include_facets include_facets
     *
     * @return self
     */
    public function setIncludeFacets($include_facets)
    {
        if (is_null($include_facets)) {
            throw new \InvalidArgumentException('non-nullable include_facets cannot be null');
        }
        $this->container['include_facets'] = $include_facets;

        return $this;
    }

    /**
     * Gets context
     *
     * @return string[]|null
     */
    public function getContext()
    {
        return $this->container['context'];
    }

    /**
     * Sets context
     *
     * @param string[]|null $context List of context strings for a search query
     *
     * @return self
     */
    public function setContext($context)
    {
        if (is_null($context)) {
            throw new \InvalidArgumentException('non-nullable context cannot be null');
        }
        $this->container['context'] = $context;

        return $this;
    }

    /**
     * Gets display_attributes
     *
     * @return string[]|null
     */
    public function getDisplayAttributes()
    {
        return $this->container['display_attributes'];
    }

    /**
     * Sets display_attributes
     *
     * @param string[]|null $display_attributes List of ad hoc attributes that will be returned for each found item
     *
     * @return self
     */
    public function setDisplayAttributes($display_attributes)
    {
        if (is_null($display_attributes)) {
            throw new \InvalidArgumentException('non-nullable display_attributes cannot be null');
        }
        $this->container['display_attributes'] = $display_attributes;

        return $this;
    }

    /**
     * Gets ignore_query_rules
     *
     * @return bool|null
     */
    public function getIgnoreQueryRules()
    {
        return $this->container['ignore_query_rules'];
    }

    /**
     * Sets ignore_query_rules
     *
     * @param bool|null $ignore_query_rules If set to `true`, query rules are not applied.
     *
     * @return self
     */
    public function setIgnoreQueryRules($ignore_query_rules)
    {
        if (is_null($ignore_query_rules)) {
            throw new \InvalidArgumentException('non-nullable ignore_query_rules cannot be null');
        }
        $this->container['ignore_query_rules'] = $ignore_query_rules;

        return $this;
    }

    /**
     * Gets exclude_query_rules
     *
     * @return int[]|null
     */
    public function getExcludeQueryRules()
    {
        return $this->container['exclude_query_rules'];
    }

    /**
     * Sets exclude_query_rules
     *
     * @param int[]|null $exclude_query_rules List of query rules that will not be applied.
     *
     * @return self
     */
    public function setExcludeQueryRules($exclude_query_rules)
    {
        if (is_null($exclude_query_rules)) {
            throw new \InvalidArgumentException('non-nullable exclude_query_rules cannot be null');
        }
        $this->container['exclude_query_rules'] = $exclude_query_rules;

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


