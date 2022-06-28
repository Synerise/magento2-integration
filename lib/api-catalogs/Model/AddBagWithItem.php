<?php
/**
 * AddBagWithItem
 *
 * PHP version 7.2
 *
 * @category Class
 * @package  Synerise\CatalogsApiClient
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 */

/**
 * Items-collector API Reference
 *
 * This is API for Catalogs
 *
 * The version of the OpenAPI document: 1.0.0
 * Contact: marcin.stelmach@synerise.com
 * Generated by: https://openapi-generator.tech
 * OpenAPI Generator version: 5.1.0-SNAPSHOT
 */

/**
 * NOTE: This class is auto generated by OpenAPI Generator (https://openapi-generator.tech).
 * https://openapi-generator.tech
 * Do not edit the class manually.
 */

namespace Synerise\CatalogsApiClient\Model;

use \ArrayAccess;
use \Synerise\CatalogsApiClient\ObjectSerializer;

/**
 * AddBagWithItem Class Doc Comment
 *
 * @category Class
 * @package  Synerise\CatalogsApiClient
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 * @implements \ArrayAccess<TKey, TValue>
 * @template TKey int|null
 * @template TValue mixed|null  
 */
class AddBagWithItem implements ModelInterface, ArrayAccess, \JsonSerializable
{
    public const DISCRIMINATOR = null;

    /**
      * The original name of the model.
      *
      * @var string
      */
    protected static $openAPIModelName = 'addBagWithItem';

    /**
      * Array of property to type mappings. Used for (de)serialization
      *
      * @var string[]
      */
    protected static $openAPITypes = [
        'bp_id' => 'int',
        'bag_name' => 'string',
        'item_key' => 'string',
        'item_value' => 'string'
    ];

    /**
      * Array of property to format mappings. Used for (de)serialization
      *
      * @var string[]
      * @phpstan-var array<string, string|null>
      * @psalm-var array<string, string|null>
      */
    protected static $openAPIFormats = [
        'bp_id' => null,
        'bag_name' => null,
        'item_key' => null,
        'item_value' => null
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
        'bp_id' => 'bpId',
        'bag_name' => 'bagName',
        'item_key' => 'itemKey',
        'item_value' => 'itemValue'
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'bp_id' => 'setBpId',
        'bag_name' => 'setBagName',
        'item_key' => 'setItemKey',
        'item_value' => 'setItemValue'
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'bp_id' => 'getBpId',
        'bag_name' => 'getBagName',
        'item_key' => 'getItemKey',
        'item_value' => 'getItemValue'
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
        $this->container['bp_id'] = $data['bp_id'] ?? null;
        $this->container['bag_name'] = $data['bag_name'] ?? null;
        $this->container['item_key'] = $data['item_key'] ?? null;
        $this->container['item_value'] = $data['item_value'] ?? null;
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
     * Gets bp_id
     *
     * @return int|null
     */
    public function getBpId()
    {
        return $this->container['bp_id'];
    }

    /**
     * Sets bp_id
     *
     * @param int|null $bp_id bp_id
     *
     * @return self
     */
    public function setBpId($bp_id)
    {
        $this->container['bp_id'] = $bp_id;

        return $this;
    }

    /**
     * Gets bag_name
     *
     * @return string|null
     */
    public function getBagName()
    {
        return $this->container['bag_name'];
    }

    /**
     * Sets bag_name
     *
     * @param string|null $bag_name bag_name
     *
     * @return self
     */
    public function setBagName($bag_name)
    {
        $this->container['bag_name'] = $bag_name;

        return $this;
    }

    /**
     * Gets item_key
     *
     * @return string|null
     */
    public function getItemKey()
    {
        return $this->container['item_key'];
    }

    /**
     * Sets item_key
     *
     * @param string|null $item_key item_key
     *
     * @return self
     */
    public function setItemKey($item_key)
    {
        $this->container['item_key'] = $item_key;

        return $this;
    }

    /**
     * Gets item_value
     *
     * @return string|null
     */
    public function getItemValue()
    {
        return $this->container['item_value'];
    }

    /**
     * Sets item_value
     *
     * @param string|null $item_value item_value
     *
     * @return self
     */
    public function setItemValue($item_value)
    {
        $this->container['item_value'] = $item_value;

        return $this;
    }
    /**
     * Returns true if offset exists. False otherwise.
     *
     * @param integer $offset Offset
     *
     * @return boolean
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
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


