<?php
/**
 * EventParamsProductAddRemove
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
 * EventParamsProductAddRemove Class Doc Comment
 *
 * @category Class
 * @description Additional parameters. The schema of this object is not exhaustive. You can add your own params, with any names. Remember that you can use event enrichment to add the data automatically from a catalog.  &lt;span style&#x3D;\&quot;color:red\&quot;&gt;&lt;strong&gt;WARNING:&lt;/strong&gt;&lt;/span&gt;  Some params are reserved for system use. If you send them in the &#x60;params&#x60; object, they are ignored or overwritten with system-assigned values: &lt;details&gt;&lt;summary&gt;Click to expand the list of reserved params&lt;/summary&gt; &lt;code&gt;modifiedBy&lt;/code&gt;&lt;br&gt; &lt;code&gt;apiKey&lt;/code&gt;&lt;br&gt; &lt;code&gt;eventUUID&lt;/code&gt;&lt;br&gt; &lt;code&gt;correlationId&lt;/code&gt;&lt;br&gt; &lt;code&gt;ip&lt;/code&gt;&lt;br&gt; &lt;code&gt;time&lt;/code&gt;&lt;br&gt; &lt;code&gt;businessProfileId&lt;/code&gt; &lt;/details&gt;
 * @package  Synerise\ApiClient
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 * @implements \ArrayAccess<TKey, TValue>
 * @template TKey int|null
 * @template TValue mixed|null  
 */
class EventParamsProductAddRemove implements ModelInterface, ArrayAccess, \JsonSerializable
{
    public const DISCRIMINATOR = null;

    /**
      * The original name of the model.
      *
      * @var string
      */
    protected static $openAPIModelName = 'EventParamsProductAddRemove';

    /**
      * Array of property to type mappings. Used for (de)serialization
      *
      * @var string[]
      */
    protected static $openAPITypes = [
        'sku' => 'string',
        'name' => 'string',
        'source' => '\Synerise\ApiClient\Model\EventSource',
        'category' => 'string',
        'categories' => 'string[]',
        'offline' => 'bool',
        'regular_unit_price' => '\Synerise\ApiClient\Model\RegularUnitPrice',
        'discounted_unit_price' => '\Synerise\ApiClient\Model\DiscountedUnitPrice',
        'final_unit_price' => '\Synerise\ApiClient\Model\FinalUnitPrice',
        'url' => 'string',
        'producer' => 'string',
        'quantity' => 'float'
    ];

    /**
      * Array of property to format mappings. Used for (de)serialization
      *
      * @var string[]
      * @phpstan-var array<string, string|null>
      * @psalm-var array<string, string|null>
      */
    protected static $openAPIFormats = [
        'sku' => null,
        'name' => null,
        'source' => null,
        'category' => null,
        'categories' => null,
        'offline' => null,
        'regular_unit_price' => null,
        'discounted_unit_price' => null,
        'final_unit_price' => null,
        'url' => null,
        'producer' => null,
        'quantity' => null
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
        'sku' => 'sku',
        'name' => 'name',
        'source' => 'source',
        'category' => 'category',
        'categories' => 'categories',
        'offline' => 'offline',
        'regular_unit_price' => 'regularUnitPrice',
        'discounted_unit_price' => 'discountedUnitPrice',
        'final_unit_price' => 'finalUnitPrice',
        'url' => 'url',
        'producer' => 'producer',
        'quantity' => 'quantity'
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'sku' => 'setSku',
        'name' => 'setName',
        'source' => 'setSource',
        'category' => 'setCategory',
        'categories' => 'setCategories',
        'offline' => 'setOffline',
        'regular_unit_price' => 'setRegularUnitPrice',
        'discounted_unit_price' => 'setDiscountedUnitPrice',
        'final_unit_price' => 'setFinalUnitPrice',
        'url' => 'setUrl',
        'producer' => 'setProducer',
        'quantity' => 'setQuantity'
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'sku' => 'getSku',
        'name' => 'getName',
        'source' => 'getSource',
        'category' => 'getCategory',
        'categories' => 'getCategories',
        'offline' => 'getOffline',
        'regular_unit_price' => 'getRegularUnitPrice',
        'discounted_unit_price' => 'getDiscountedUnitPrice',
        'final_unit_price' => 'getFinalUnitPrice',
        'url' => 'getUrl',
        'producer' => 'getProducer',
        'quantity' => 'getQuantity'
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
        $this->container['sku'] = $data['sku'] ?? null;
        $this->container['name'] = $data['name'] ?? null;
        $this->container['source'] = $data['source'] ?? null;
        $this->container['category'] = $data['category'] ?? null;
        $this->container['categories'] = $data['categories'] ?? null;
        $this->container['offline'] = $data['offline'] ?? null;
        $this->container['regular_unit_price'] = $data['regular_unit_price'] ?? null;
        $this->container['discounted_unit_price'] = $data['discounted_unit_price'] ?? null;
        $this->container['final_unit_price'] = $data['final_unit_price'] ?? null;
        $this->container['url'] = $data['url'] ?? null;
        $this->container['producer'] = $data['producer'] ?? null;
        $this->container['quantity'] = $data['quantity'] ?? null;
    }

    /**
     * Show all the invalid properties with reasons.
     *
     * @return array invalid properties with reasons
     */
    public function listInvalidProperties()
    {
        $invalidProperties = [];

        if ($this->container['sku'] === null) {
            $invalidProperties[] = "'sku' can't be null";
        }
        if ($this->container['source'] === null) {
            $invalidProperties[] = "'source' can't be null";
        }
        if ($this->container['final_unit_price'] === null) {
            $invalidProperties[] = "'final_unit_price' can't be null";
        }
        if ($this->container['quantity'] === null) {
            $invalidProperties[] = "'quantity' can't be null";
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
     * Gets sku
     *
     * @return string
     */
    public function getSku()
    {
        return $this->container['sku'];
    }

    /**
     * Sets sku
     *
     * @param string $sku SKU of the item
     *
     * @return self
     */
    public function setSku($sku)
    {
        $this->container['sku'] = $sku;

        return $this;
    }

    /**
     * Gets name
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->container['name'];
    }

    /**
     * Sets name
     *
     * @param string|null $name Item name
     *
     * @return self
     */
    public function setName($name)
    {
        $this->container['name'] = $name;

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
     * Gets category
     *
     * @return string|null
     */
    public function getCategory()
    {
        return $this->container['category'];
    }

    /**
     * Sets category
     *
     * @param string|null $category Item category
     *
     * @return self
     */
    public function setCategory($category)
    {
        $this->container['category'] = $category;

        return $this;
    }

    /**
     * Gets categories
     *
     * @return string[]|null
     */
    public function getCategories()
    {
        return $this->container['categories'];
    }

    /**
     * Sets categories
     *
     * @param string[]|null $categories If an item belongs to more than one category, include the categories in an array
     *
     * @return self
     */
    public function setCategories($categories)
    {
        $this->container['categories'] = $categories;

        return $this;
    }

    /**
     * Gets offline
     *
     * @return bool|null
     */
    public function getOffline()
    {
        return $this->container['offline'];
    }

    /**
     * Sets offline
     *
     * @param bool|null $offline Set to `true` if the event occurred outside a website, for example in a cash register
     *
     * @return self
     */
    public function setOffline($offline)
    {
        $this->container['offline'] = $offline;

        return $this;
    }

    /**
     * Gets regular_unit_price
     *
     * @return \Synerise\ApiClient\Model\RegularUnitPrice|null
     */
    public function getRegularUnitPrice()
    {
        return $this->container['regular_unit_price'];
    }

    /**
     * Sets regular_unit_price
     *
     * @param \Synerise\ApiClient\Model\RegularUnitPrice|null $regular_unit_price regular_unit_price
     *
     * @return self
     */
    public function setRegularUnitPrice($regular_unit_price)
    {
        $this->container['regular_unit_price'] = $regular_unit_price;

        return $this;
    }

    /**
     * Gets discounted_unit_price
     *
     * @return \Synerise\ApiClient\Model\DiscountedUnitPrice|null
     */
    public function getDiscountedUnitPrice()
    {
        return $this->container['discounted_unit_price'];
    }

    /**
     * Sets discounted_unit_price
     *
     * @param \Synerise\ApiClient\Model\DiscountedUnitPrice|null $discounted_unit_price discounted_unit_price
     *
     * @return self
     */
    public function setDiscountedUnitPrice($discounted_unit_price)
    {
        $this->container['discounted_unit_price'] = $discounted_unit_price;

        return $this;
    }

    /**
     * Gets final_unit_price
     *
     * @return \Synerise\ApiClient\Model\FinalUnitPrice
     */
    public function getFinalUnitPrice()
    {
        return $this->container['final_unit_price'];
    }

    /**
     * Sets final_unit_price
     *
     * @param \Synerise\ApiClient\Model\FinalUnitPrice $final_unit_price final_unit_price
     *
     * @return self
     */
    public function setFinalUnitPrice($final_unit_price)
    {
        $this->container['final_unit_price'] = $final_unit_price;

        return $this;
    }

    /**
     * Gets url
     *
     * @return string|null
     */
    public function getUrl()
    {
        return $this->container['url'];
    }

    /**
     * Sets url
     *
     * @param string|null $url URL address of the product page
     *
     * @return self
     */
    public function setUrl($url)
    {
        $this->container['url'] = $url;

        return $this;
    }

    /**
     * Gets producer
     *
     * @return string|null
     */
    public function getProducer()
    {
        return $this->container['producer'];
    }

    /**
     * Sets producer
     *
     * @param string|null $producer Manufacturer of the product
     *
     * @return self
     */
    public function setProducer($producer)
    {
        $this->container['producer'] = $producer;

        return $this;
    }

    /**
     * Gets quantity
     *
     * @return float
     */
    public function getQuantity()
    {
        return $this->container['quantity'];
    }

    /**
     * Sets quantity
     *
     * @param float $quantity The amount of goods
     *
     * @return self
     */
    public function setQuantity($quantity)
    {
        $this->container['quantity'] = $quantity;

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


