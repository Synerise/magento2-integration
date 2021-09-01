<?php
/**
 * EventSource
 *
 * PHP version 7.2
 *
 * @category Class
 * @package  Synerise\ApiClient
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 */

/**
 * apiV4
 *
 * No description provided (generated by Openapi Generator https://github.com/openapitools/openapi-generator)
 *
 * The version of the OpenAPI document: 4.4
 * 
 * Generated by: https://openapi-generator.tech
 * OpenAPI Generator version: 5.1.0-SNAPSHOT
 */

/**
 * NOTE: This class is auto generated by OpenAPI Generator (https://openapi-generator.tech).
 * https://openapi-generator.tech
 * Do not edit the class manually.
 */

namespace Synerise\ApiClient\Model;
use \Synerise\ApiClient\ObjectSerializer;

/**
 * EventSource Class Doc Comment
 *
 * @category Class
 * @description Type of device
 * @package  Synerise\ApiClient
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 */
class EventSource
{
    /**
     * Possible values of this enum
     */
    const WEB_DESKTOP = 'WEB_DESKTOP';
    const WEB_MOBILE = 'WEB_MOBILE';
    const MOBILE_APP = 'MOBILE_APP';
    const POS = 'POS';
    const MOBILE = 'MOBILE';
    const DESKTOP = 'DESKTOP';
    
    /**
     * Gets allowable values of the enum
     * @return string[]
     */
    public static function getAllowableEnumValues()
    {
        return [
            self::WEB_DESKTOP,
            self::WEB_MOBILE,
            self::MOBILE_APP,
            self::POS,
            self::MOBILE,
            self::DESKTOP,
        ];
    }
}


