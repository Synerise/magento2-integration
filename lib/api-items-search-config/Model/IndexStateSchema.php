<?php
/**
 * IndexStateSchema
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
use \Synerise\ItemsSearchConfigApiClient\ObjectSerializer;

/**
 * IndexStateSchema Class Doc Comment
 *
 * @category Class
 * @description State of the index
 * @package  Synerise\ItemsSearchConfigApiClient
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 */
class IndexStateSchema
{
    /**
     * Possible values of this enum
     */
    public const NOT_READY = 'NotReady';

    public const READY_UP_TO_DATE = 'ReadyUpToDate';

    public const READY_NOT_UP_TO_DATE = 'ReadyNotUpToDate';

    /**
     * Gets allowable values of the enum
     * @return string[]
     */
    public static function getAllowableEnumValues()
    {
        return [
            self::NOT_READY,
            self::READY_UP_TO_DATE,
            self::READY_NOT_UP_TO_DATE
        ];
    }
}


