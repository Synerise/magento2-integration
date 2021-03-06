<?php
/**
 * InBodyClientSex
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
 * InBodyClientSex Class Doc Comment
 *
 * @category Class
 * @description Client&#39;s sex
 * @package  Synerise\ApiClient
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 */
class InBodyClientSex
{
    /**
     * Possible values of this enum
     */
    const FEMALE = 'FEMALE';
    const MALE = 'MALE';
    const NOT_SPECIFIED = 'NOT_SPECIFIED';
    const OTHER = 'OTHER';
    
    /**
     * Gets allowable values of the enum
     * @return string[]
     */
    public static function getAllowableEnumValues()
    {
        return [
            self::FEMALE,
            self::MALE,
            self::NOT_SPECIFIED,
            self::OTHER,
        ];
    }
}


