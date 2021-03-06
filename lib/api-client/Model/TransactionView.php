<?php
/**
 * TransactionView
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
 * TransactionView Class Doc Comment
 *
 * @category Class
 * @package  Synerise\ApiClient
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 * @implements \ArrayAccess<TKey, TValue>
 * @template TKey int|null
 * @template TValue mixed|null  
 */
class TransactionView implements ModelInterface, ArrayAccess, \JsonSerializable
{
    public const DISCRIMINATOR = null;

    /**
      * The original name of the model.
      *
      * @var string
      */
    protected static $openAPIModelName = 'TransactionView';

    /**
      * Array of property to type mappings. Used for (de)serialization
      *
      * @var string[]
      */
    protected static $openAPITypes = [
        'client_id' => 'int',
        'online' => 'bool',
        'order_id' => 'string',
        'products' => '\Synerise\ApiClient\Model\Product[]',
        'recorded_at' => 'string',
        'stored_at' => '\DateTime',
        'source' => '\Synerise\ApiClient\Model\EventSource',
        'value' => '\Synerise\ApiClient\Model\TransactionViewValue',
        'metadata' => 'array<string,object>'
    ];

    /**
      * Array of property to format mappings. Used for (de)serialization
      *
      * @var string[]
      * @phpstan-var array<string, string|null>
      * @psalm-var array<string, string|null>
      */
    protected static $openAPIFormats = [
        'client_id' => 'int32',
        'online' => null,
        'order_id' => null,
        'products' => null,
        'recorded_at' => null,
        'stored_at' => 'date-time',
        'source' => null,
        'value' => null,
        'metadata' => null
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
        'client_id' => 'clientId',
        'online' => 'online',
        'order_id' => 'orderId',
        'products' => 'products',
        'recorded_at' => 'recordedAt',
        'stored_at' => 'storedAt',
        'source' => 'source',
        'value' => 'value',
        'metadata' => 'metadata'
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'client_id' => 'setClientId',
        'online' => 'setOnline',
        'order_id' => 'setOrderId',
        'products' => 'setProducts',
        'recorded_at' => 'setRecordedAt',
        'stored_at' => 'setStoredAt',
        'source' => 'setSource',
        'value' => 'setValue',
        'metadata' => 'setMetadata'
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'client_id' => 'getClientId',
        'online' => 'getOnline',
        'order_id' => 'getOrderId',
        'products' => 'getProducts',
        'recorded_at' => 'getRecordedAt',
        'stored_at' => 'getStoredAt',
        'source' => 'getSource',
        'value' => 'getValue',
        'metadata' => 'getMetadata'
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
        $this->container['client_id'] = $data['client_id'] ?? null;
        $this->container['online'] = $data['online'] ?? null;
        $this->container['order_id'] = $data['order_id'] ?? null;
        $this->container['products'] = $data['products'] ?? null;
        $this->container['recorded_at'] = $data['recorded_at'] ?? null;
        $this->container['stored_at'] = $data['stored_at'] ?? null;
        $this->container['source'] = $data['source'] ?? null;
        $this->container['value'] = $data['value'] ?? null;
        $this->container['metadata'] = $data['metadata'] ?? null;
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
     * Gets client_id
     *
     * @return int|null
     */
    public function getClientId()
    {
        return $this->container['client_id'];
    }

    /**
     * Sets client_id
     *
     * @param int|null $client_id Unique ID
     *
     * @return self
     */
    public function setClientId($client_id)
    {
        $this->container['client_id'] = $client_id;

        return $this;
    }

    /**
     * Gets online
     *
     * @return bool|null
     */
    public function getOnline()
    {
        return $this->container['online'];
    }

    /**
     * Sets online
     *
     * @param bool|null $online Informs if the transaction occurred in an online store.
     *
     * @return self
     */
    public function setOnline($online)
    {
        $this->container['online'] = $online;

        return $this;
    }

    /**
     * Gets order_id
     *
     * @return string|null
     */
    public function getOrderId()
    {
        return $this->container['order_id'];
    }

    /**
     * Sets order_id
     *
     * @param string|null $order_id ID of the transaction
     *
     * @return self
     */
    public function setOrderId($order_id)
    {
        $this->container['order_id'] = $order_id;

        return $this;
    }

    /**
     * Gets products
     *
     * @return \Synerise\ApiClient\Model\Product[]|null
     */
    public function getProducts()
    {
        return $this->container['products'];
    }

    /**
     * Sets products
     *
     * @param \Synerise\ApiClient\Model\Product[]|null $products A list of products in the transaction
     *
     * @return self
     */
    public function setProducts($products)
    {
        $this->container['products'] = $products;

        return $this;
    }

    /**
     * Gets recorded_at
     *
     * @return string|null
     */
    public function getRecordedAt()
    {
        return $this->container['recorded_at'];
    }

    /**
     * Sets recorded_at
     *
     * @param string|null $recorded_at Timestamp in ISO 8601. If not defined, the current time applies.  By default, the time is in UTC.  If you want to include a timezone, you can do this by adding `{+|-}hh:mm` at the end of the string. For example, if your timezone is UTC+1, add `+01:00`.  When you retrieve an event, the timestamp is calculated into UTC, even if the original POST request included a timezone. The original string with the timezone is included in the additional parameters of the event.
     *
     * @return self
     */
    public function setRecordedAt($recorded_at)
    {
        $this->container['recorded_at'] = $recorded_at;

        return $this;
    }

    /**
     * Gets stored_at
     *
     * @return \DateTime|null
     */
    public function getStoredAt()
    {
        return $this->container['stored_at'];
    }

    /**
     * Sets stored_at
     *
     * @param \DateTime|null $stored_at Time when the transaction was saved to the database.
     *
     * @return self
     */
    public function setStoredAt($stored_at)
    {
        $this->container['stored_at'] = $stored_at;

        return $this;
    }

    /**
     * Gets source
     *
     * @return \Synerise\ApiClient\Model\EventSource|null
     */
    public function getSource()
    {
        return $this->container['source'];
    }

    /**
     * Sets source
     *
     * @param \Synerise\ApiClient\Model\EventSource|null $source source
     *
     * @return self
     */
    public function setSource($source)
    {
        $this->container['source'] = $source;

        return $this;
    }

    /**
     * Gets value
     *
     * @return \Synerise\ApiClient\Model\TransactionViewValue|null
     */
    public function getValue()
    {
        return $this->container['value'];
    }

    /**
     * Sets value
     *
     * @param \Synerise\ApiClient\Model\TransactionViewValue|null $value value
     *
     * @return self
     */
    public function setValue($value)
    {
        $this->container['value'] = $value;

        return $this;
    }

    /**
     * Gets metadata
     *
     * @return array<string,object>|null
     */
    public function getMetadata()
    {
        return $this->container['metadata'];
    }

    /**
     * Sets metadata
     *
     * @param array<string,object>|null $metadata Any custom parameters
     *
     * @return self
     */
    public function setMetadata($metadata)
    {
        $this->container['metadata'] = $metadata;

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


