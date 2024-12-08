<?php
/**
 * IncludeFacets
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
use \Synerise\ItemsSearchApiClient\ObjectSerializer;

/**
 * IncludeFacets Class Doc Comment
 *
 * @category Class
 * @description Determines which groups of facets will be returned: both filtered and unfiltered; just filtered; just unfiltered; or no group at at all.  To determine which attributes should be returned as facets in each group, use the &#x60;facets&#x60; parameter.
 * @package  Synerise\ItemsSearchApiClient
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 */
class IncludeFacets
{
    /**
     * Possible values of this enum
     */
    public const ALL = 'all';

    public const FILTERED = 'filtered';

    public const UNFILTERED = 'unfiltered';

    public const NONE = 'none';

    /**
     * Gets allowable values of the enum
     * @return string[]
     */
    public static function getAllowableEnumValues()
    {
        return [
            self::ALL,
            self::FILTERED,
            self::UNFILTERED,
            self::NONE
        ];
    }
}

