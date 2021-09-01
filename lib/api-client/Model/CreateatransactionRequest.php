<?php
/**
 * CreateatransactionRequest
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
 * CreateatransactionRequest Class Doc Comment
 *
 * @category Class
 * @package  Synerise\ApiClient
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 * @implements \ArrayAccess<TKey, TValue>
 * @template TKey int|null
 * @template TValue mixed|null  
 */
class CreateatransactionRequest implements ModelInterface, ArrayAccess, \JsonSerializable
{
    public const DISCRIMINATOR = null;

    /**
      * The original name of the model.
      *
      * @var string
      */
    protected static $openAPIModelName = 'CreateatransactionRequest';

    /**
      * Array of property to type mappings. Used for (de)serialization
      *
      * @var string[]
      */
    protected static $openAPITypes = [
        'client' => '\Synerise\ApiClient\Model\Client',
        'discount_amount' => '\Synerise\ApiClient\Model\CreateatransactionRequestDiscountAmount',
        'metadata' => 'array<string,object>',
        'order_id' => 'string',
        'payment_info' => '\Synerise\ApiClient\Model\PaymentInfo',
        'products' => '\Synerise\ApiClient\Model\Product[]',
        'recorded_at' => 'string',
        'revenue' => '\Synerise\ApiClient\Model\CreateatransactionRequestRevenue',
        'value' => '\Synerise\ApiClient\Model\CreateatransactionRequestValue',
        'source' => '\Synerise\ApiClient\Model\EventSource',
        'event_salt' => 'string'
    ];

    /**
      * Array of property to format mappings. Used for (de)serialization
      *
      * @var string[]
      * @phpstan-var array<string, string|null>
      * @psalm-var array<string, string|null>
      */
    protected static $openAPIFormats = [
        'client' => null,
        'discount_amount' => null,
        'metadata' => null,
        'order_id' => null,
        'payment_info' => null,
        'products' => null,
        'recorded_at' => null,
        'revenue' => null,
        'value' => null,
        'source' => null,
        'event_salt' => null
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
        'client' => 'client',
        'discount_amount' => 'discountAmount',
        'metadata' => 'metadata',
        'order_id' => 'orderId',
        'payment_info' => 'paymentInfo',
        'products' => 'products',
        'recorded_at' => 'recordedAt',
        'revenue' => 'revenue',
        'value' => 'value',
        'source' => 'source',
        'event_salt' => 'eventSalt'
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'client' => 'setClient',
        'discount_amount' => 'setDiscountAmount',
        'metadata' => 'setMetadata',
        'order_id' => 'setOrderId',
        'payment_info' => 'setPaymentInfo',
        'products' => 'setProducts',
        'recorded_at' => 'setRecordedAt',
        'revenue' => 'setRevenue',
        'value' => 'setValue',
        'source' => 'setSource',
        'event_salt' => 'setEventSalt'
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'client' => 'getClient',
        'discount_amount' => 'getDiscountAmount',
        'metadata' => 'getMetadata',
        'order_id' => 'getOrderId',
        'payment_info' => 'getPaymentInfo',
        'products' => 'getProducts',
        'recorded_at' => 'getRecordedAt',
        'revenue' => 'getRevenue',
        'value' => 'getValue',
        'source' => 'getSource',
        'event_salt' => 'getEventSalt'
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
        $this->container['client'] = $data['client'] ?? null;
        $this->container['discount_amount'] = $data['discount_amount'] ?? null;
        $this->container['metadata'] = $data['metadata'] ?? null;
        $this->container['order_id'] = $data['order_id'] ?? null;
        $this->container['payment_info'] = $data['payment_info'] ?? null;
        $this->container['products'] = $data['products'] ?? null;
        $this->container['recorded_at'] = $data['recorded_at'] ?? null;
        $this->container['revenue'] = $data['revenue'] ?? null;
        $this->container['value'] = $data['value'] ?? null;
        $this->container['source'] = $data['source'] ?? null;
        $this->container['event_salt'] = $data['event_salt'] ?? null;
    }

    /**
     * Show all the invalid properties with reasons.
     *
     * @return array invalid properties with reasons
     */
    public function listInvalidProperties()
    {
        $invalidProperties = [];

        if ($this->container['client'] === null) {
            $invalidProperties[] = "'client' can't be null";
        }
        if ($this->container['order_id'] === null) {
            $invalidProperties[] = "'order_id' can't be null";
        }
        if ($this->container['payment_info'] === null) {
            $invalidProperties[] = "'payment_info' can't be null";
        }
        if ($this->container['products'] === null) {
            $invalidProperties[] = "'products' can't be null";
        }
        if ($this->container['revenue'] === null) {
            $invalidProperties[] = "'revenue' can't be null";
        }
        if ($this->container['value'] === null) {
            $invalidProperties[] = "'value' can't be null";
        }
        if ($this->container['source'] === null) {
            $invalidProperties[] = "'source' can't be null";
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
     * Gets client
     *
     * @return \Synerise\ApiClient\Model\Client
     */
    public function getClient()
    {
        return $this->container['client'];
    }

    /**
     * Sets client
     *
     * @param \Synerise\ApiClient\Model\Client $client client
     *
     * @return self
     */
    public function setClient($client)
    {
        $this->container['client'] = $client;

        return $this;
    }

    /**
     * Gets discount_amount
     *
     * @return \Synerise\ApiClient\Model\CreateatransactionRequestDiscountAmount|null
     */
    public function getDiscountAmount()
    {
        return $this->container['discount_amount'];
    }

    /**
     * Sets discount_amount
     *
     * @param \Synerise\ApiClient\Model\CreateatransactionRequestDiscountAmount|null $discount_amount discount_amount
     *
     * @return self
     */
    public function setDiscountAmount($discount_amount)
    {
        $this->container['discount_amount'] = $discount_amount;

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
     * Gets order_id
     *
     * @return string
     */
    public function getOrderId()
    {
        return $this->container['order_id'];
    }

    /**
     * Sets order_id
     *
     * @param string $order_id ID of the transaction
     *
     * @return self
     */
    public function setOrderId($order_id)
    {
        $this->container['order_id'] = $order_id;

        return $this;
    }

    /**
     * Gets payment_info
     *
     * @return \Synerise\ApiClient\Model\PaymentInfo
     */
    public function getPaymentInfo()
    {
        return $this->container['payment_info'];
    }

    /**
     * Sets payment_info
     *
     * @param \Synerise\ApiClient\Model\PaymentInfo $payment_info payment_info
     *
     * @return self
     */
    public function setPaymentInfo($payment_info)
    {
        $this->container['payment_info'] = $payment_info;

        return $this;
    }

    /**
     * Gets products
     *
     * @return \Synerise\ApiClient\Model\Product[]
     */
    public function getProducts()
    {
        return $this->container['products'];
    }

    /**
     * Sets products
     *
     * @param \Synerise\ApiClient\Model\Product[] $products A list of products in the transaction
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
     * Gets revenue
     *
     * @return \Synerise\ApiClient\Model\CreateatransactionRequestRevenue
     */
    public function getRevenue()
    {
        return $this->container['revenue'];
    }

    /**
     * Sets revenue
     *
     * @param \Synerise\ApiClient\Model\CreateatransactionRequestRevenue $revenue revenue
     *
     * @return self
     */
    public function setRevenue($revenue)
    {
        $this->container['revenue'] = $revenue;

        return $this;
    }

    /**
     * Gets value
     *
     * @return \Synerise\ApiClient\Model\CreateatransactionRequestValue
     */
    public function getValue()
    {
        return $this->container['value'];
    }

    /**
     * Sets value
     *
     * @param \Synerise\ApiClient\Model\CreateatransactionRequestValue $value value
     *
     * @return self
     */
    public function setValue($value)
    {
        $this->container['value'] = $value;

        return $this;
    }

    /**
     * Gets source
     *
     * @return \Synerise\ApiClient\Model\EventSource
     */
    public function getSource()
    {
        return $this->container['source'];
    }

    /**
     * Sets source
     *
     * @param \Synerise\ApiClient\Model\EventSource $source source
     *
     * @return self
     */
    public function setSource($source)
    {
        $this->container['source'] = $source;

        return $this;
    }

    /**
     * Gets event_salt
     *
     * @return string|null
     */
    public function getEventSalt()
    {
        return $this->container['event_salt'];
    }

    /**
     * Sets event_salt
     *
     * @param string|null $event_salt This parameter is used to generate an UUID for the transaction event. When a transaction has an `eventSalt`, it can be overwritten by sending another transaction with the same `eventSalt` and `recordedAt` as the original transaction.  A transaction that has no `eventSalt` cannot be overwritten. The parameter cannot be added at a later time.  **IMPORTANT:** - `eventSalt` must be unique in the whole system. - The parameter cannot be retrieved later. You must keep track of the values that you send.
     *
     * @return self
     */
    public function setEventSalt($event_salt)
    {
        $this->container['event_salt'] = $event_salt;

        return $this;
    }
    /**
     * Returns true if offset exists. False otherwise.
     *
     * @param integer $offset Offset
     *
     * @return boolean
     */
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


