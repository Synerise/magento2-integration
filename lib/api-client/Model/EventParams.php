<?php
/**
 * EventParams
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

use \ArrayAccess;
use \Synerise\ApiClient\ObjectSerializer;

/**
 * EventParams Class Doc Comment
 *
 * @category Class
 * @description Additional parameters. The schema of this object is not exhaustive. You can add your own params, with any names.  &lt;span style&#x3D;\&quot;color:red\&quot;&gt;&lt;strong&gt;WARNING:&lt;/strong&gt;&lt;/span&gt;  Some params are reserved for system use. If you send them in the &#x60;params&#x60; object, they are ignored or overwritten with system-assigned values: &lt;details&gt;&lt;summary&gt;Click to expand the list of reserved params&lt;/summary&gt; &lt;code&gt;modifiedBy&lt;/code&gt;&lt;br&gt; &lt;code&gt;apiKey&lt;/code&gt;&lt;br&gt; &lt;code&gt;eventUUID&lt;/code&gt;&lt;br&gt; &lt;code&gt;correlationId&lt;/code&gt;&lt;br&gt; &lt;code&gt;ip&lt;/code&gt;&lt;br&gt; &lt;code&gt;time&lt;/code&gt;&lt;br&gt; &lt;code&gt;businessProfileId&lt;/code&gt; &lt;/details&gt;
 * @package  Synerise\ApiClient
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 * @implements \ArrayAccess<TKey, TValue>
 * @template TKey int|null
 * @template TValue mixed|null  
 */
class EventParams implements ModelInterface, ArrayAccess, \JsonSerializable
{
    public const DISCRIMINATOR = null;

    /**
      * The original name of the model.
      *
      * @var string
      */
    protected static $openAPIModelName = 'eventParams';

    /**
      * Array of property to type mappings. Used for (de)serialization
      *
      * @var string[]
      */
    protected static $openAPITypes = [
        'sdk_version' => 'string',
        'sdk_version_code' => 'string',
        'application_name' => 'string',
        'version' => 'string',
        'app_version_code' => 'string',
        'device_id' => 'string',
        'device_model' => 'string',
        'device_manufacturer' => 'string',
        'device_resolution' => 'string',
        'device_type' => 'string',
        'os' => 'string',
        'os_version' => 'string',
        'os_language' => 'string'
    ];

    /**
      * Array of property to format mappings. Used for (de)serialization
      *
      * @var string[]
      * @phpstan-var array<string, string|null>
      * @psalm-var array<string, string|null>
      */
    protected static $openAPIFormats = [
        'sdk_version' => null,
        'sdk_version_code' => null,
        'application_name' => null,
        'version' => null,
        'app_version_code' => null,
        'device_id' => null,
        'device_model' => null,
        'device_manufacturer' => null,
        'device_resolution' => null,
        'device_type' => null,
        'os' => null,
        'os_version' => null,
        'os_language' => null
    ];

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
     * Array of attributes where the key is the local name,
     * and the value is the original name
     *
     * @var string[]
     */
    protected static $attributeMap = [
        'sdk_version' => 'sdkVersion',
        'sdk_version_code' => 'sdkVersionCode',
        'application_name' => 'applicationName',
        'version' => 'version',
        'app_version_code' => 'appVersionCode',
        'device_id' => 'deviceID',
        'device_model' => 'deviceModel',
        'device_manufacturer' => 'deviceManufacturer',
        'device_resolution' => 'deviceResolution',
        'device_type' => 'deviceType',
        'os' => 'os',
        'os_version' => 'osVersion',
        'os_language' => 'osLanguage'
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'sdk_version' => 'setSdkVersion',
        'sdk_version_code' => 'setSdkVersionCode',
        'application_name' => 'setApplicationName',
        'version' => 'setVersion',
        'app_version_code' => 'setAppVersionCode',
        'device_id' => 'setDeviceId',
        'device_model' => 'setDeviceModel',
        'device_manufacturer' => 'setDeviceManufacturer',
        'device_resolution' => 'setDeviceResolution',
        'device_type' => 'setDeviceType',
        'os' => 'setOs',
        'os_version' => 'setOsVersion',
        'os_language' => 'setOsLanguage'
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'sdk_version' => 'getSdkVersion',
        'sdk_version_code' => 'getSdkVersionCode',
        'application_name' => 'getApplicationName',
        'version' => 'getVersion',
        'app_version_code' => 'getAppVersionCode',
        'device_id' => 'getDeviceId',
        'device_model' => 'getDeviceModel',
        'device_manufacturer' => 'getDeviceManufacturer',
        'device_resolution' => 'getDeviceResolution',
        'device_type' => 'getDeviceType',
        'os' => 'getOs',
        'os_version' => 'getOsVersion',
        'os_language' => 'getOsLanguage'
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

    const OS_ANDROID = 'android';
    const OS_IOS = 'ios';
    const OS_WINDOWS = 'windows';
    

    
    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getOsAllowableValues()
    {
        return [
            self::OS_ANDROID,
            self::OS_IOS,
            self::OS_WINDOWS,
        ];
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
        $this->container['sdk_version'] = $data['sdk_version'] ?? null;
        $this->container['sdk_version_code'] = $data['sdk_version_code'] ?? null;
        $this->container['application_name'] = $data['application_name'] ?? null;
        $this->container['version'] = $data['version'] ?? null;
        $this->container['app_version_code'] = $data['app_version_code'] ?? null;
        $this->container['device_id'] = $data['device_id'] ?? null;
        $this->container['device_model'] = $data['device_model'] ?? null;
        $this->container['device_manufacturer'] = $data['device_manufacturer'] ?? null;
        $this->container['device_resolution'] = $data['device_resolution'] ?? null;
        $this->container['device_type'] = $data['device_type'] ?? null;
        $this->container['os'] = $data['os'] ?? null;
        $this->container['os_version'] = $data['os_version'] ?? null;
        $this->container['os_language'] = $data['os_language'] ?? null;
    }

    /**
     * Show all the invalid properties with reasons.
     *
     * @return array invalid properties with reasons
     */
    public function listInvalidProperties()
    {
        $invalidProperties = [];

        if ($this->container['application_name'] === null) {
            $invalidProperties[] = "'application_name' can't be null";
        }
        if ($this->container['version'] === null) {
            $invalidProperties[] = "'version' can't be null";
        }
        $allowedValues = $this->getOsAllowableValues();
        if (!is_null($this->container['os']) && !in_array($this->container['os'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'os', must be one of '%s'",
                $this->container['os'],
                implode("', '", $allowedValues)
            );
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
     * Gets sdk_version
     *
     * @return string|null
     */
    public function getSdkVersion()
    {
        return $this->container['sdk_version'];
    }

    /**
     * Sets sdk_version
     *
     * @param string|null $sdk_version Added automatically by the SDK
     *
     * @return self
     */
    public function setSdkVersion($sdk_version)
    {
        $this->container['sdk_version'] = $sdk_version;

        return $this;
    }

    /**
     * Gets sdk_version_code
     *
     * @return string|null
     */
    public function getSdkVersionCode()
    {
        return $this->container['sdk_version_code'];
    }

    /**
     * Sets sdk_version_code
     *
     * @param string|null $sdk_version_code Added automatically by the SDK
     *
     * @return self
     */
    public function setSdkVersionCode($sdk_version_code)
    {
        $this->container['sdk_version_code'] = $sdk_version_code;

        return $this;
    }

    /**
     * Gets application_name
     *
     * @return string
     */
    public function getApplicationName()
    {
        return $this->container['application_name'];
    }

    /**
     * Sets application_name
     *
     * @param string $application_name Name of the application that sends the event
     *
     * @return self
     */
    public function setApplicationName($application_name)
    {
        $this->container['application_name'] = $application_name;

        return $this;
    }

    /**
     * Gets version
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->container['version'];
    }

    /**
     * Sets version
     *
     * @param string $version Version of the application that sends the event
     *
     * @return self
     */
    public function setVersion($version)
    {
        $this->container['version'] = $version;

        return $this;
    }

    /**
     * Gets app_version_code
     *
     * @return string|null
     */
    public function getAppVersionCode()
    {
        return $this->container['app_version_code'];
    }

    /**
     * Sets app_version_code
     *
     * @param string|null $app_version_code Version code of the application that sends the event
     *
     * @return self
     */
    public function setAppVersionCode($app_version_code)
    {
        $this->container['app_version_code'] = $app_version_code;

        return $this;
    }

    /**
     * Gets device_id
     *
     * @return string|null
     */
    public function getDeviceId()
    {
        return $this->container['device_id'];
    }

    /**
     * Sets device_id
     *
     * @param string|null $device_id Unique Android or iOS device ID
     *
     * @return self
     */
    public function setDeviceId($device_id)
    {
        $this->container['device_id'] = $device_id;

        return $this;
    }

    /**
     * Gets device_model
     *
     * @return string|null
     */
    public function getDeviceModel()
    {
        return $this->container['device_model'];
    }

    /**
     * Sets device_model
     *
     * @param string|null $device_model Model of the device
     *
     * @return self
     */
    public function setDeviceModel($device_model)
    {
        $this->container['device_model'] = $device_model;

        return $this;
    }

    /**
     * Gets device_manufacturer
     *
     * @return string|null
     */
    public function getDeviceManufacturer()
    {
        return $this->container['device_manufacturer'];
    }

    /**
     * Sets device_manufacturer
     *
     * @param string|null $device_manufacturer Manufacturer of the device
     *
     * @return self
     */
    public function setDeviceManufacturer($device_manufacturer)
    {
        $this->container['device_manufacturer'] = $device_manufacturer;

        return $this;
    }

    /**
     * Gets device_resolution
     *
     * @return string|null
     */
    public function getDeviceResolution()
    {
        return $this->container['device_resolution'];
    }

    /**
     * Sets device_resolution
     *
     * @param string|null $device_resolution Screen resolution in pixels
     *
     * @return self
     */
    public function setDeviceResolution($device_resolution)
    {
        $this->container['device_resolution'] = $device_resolution;

        return $this;
    }

    /**
     * Gets device_type
     *
     * @return string|null
     */
    public function getDeviceType()
    {
        return $this->container['device_type'];
    }

    /**
     * Sets device_type
     *
     * @param string|null $device_type The type of device that sends the event, for example \"MOBILE\"
     *
     * @return self
     */
    public function setDeviceType($device_type)
    {
        $this->container['device_type'] = $device_type;

        return $this;
    }

    /**
     * Gets os
     *
     * @return string|null
     */
    public function getOs()
    {
        return $this->container['os'];
    }

    /**
     * Sets os
     *
     * @param string|null $os Operating system of the device
     *
     * @return self
     */
    public function setOs($os)
    {
        $allowedValues = $this->getOsAllowableValues();
        if (!is_null($os) && !in_array($os, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'os', must be one of '%s'",
                    $os,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['os'] = $os;

        return $this;
    }

    /**
     * Gets os_version
     *
     * @return string|null
     */
    public function getOsVersion()
    {
        return $this->container['os_version'];
    }

    /**
     * Sets os_version
     *
     * @param string|null $os_version Version of the operating system
     *
     * @return self
     */
    public function setOsVersion($os_version)
    {
        $this->container['os_version'] = $os_version;

        return $this;
    }

    /**
     * Gets os_language
     *
     * @return string|null
     */
    public function getOsLanguage()
    {
        return $this->container['os_language'];
    }

    /**
     * Sets os_language
     *
     * @param string|null $os_language Language of the operating system
     *
     * @return self
     */
    public function setOsLanguage($os_language)
    {
        $this->container['os_language'] = $os_language;

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
    public function offsetGet($offset): mixed
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
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
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
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
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


