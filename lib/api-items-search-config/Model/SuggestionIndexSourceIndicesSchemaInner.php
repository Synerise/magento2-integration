<?php
/**
 * SuggestionIndexSourceIndicesSchemaInner
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

use \ArrayAccess;
use \Synerise\ItemsSearchConfigApiClient\ObjectSerializer;

/**
 * SuggestionIndexSourceIndicesSchemaInner Class Doc Comment
 *
 * @category Class
 * @package  Synerise\ItemsSearchConfigApiClient
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 * @implements \ArrayAccess<string, mixed>
 */
class SuggestionIndexSourceIndicesSchemaInner implements ModelInterface, ArrayAccess, \JsonSerializable
{
    public const DISCRIMINATOR = null;

    /**
      * The original name of the model.
      *
      * @var string
      */
    protected static $openAPIModelName = 'SuggestionIndexSourceIndicesSchema_inner';

    /**
      * Array of property to type mappings. Used for (de)serialization
      *
      * @var string[]
      */
    protected static $openAPITypes = [
        'index_id' => 'string',
        'min_popularity' => 'int',
        'min_hits' => 'int',
        'min_letters' => 'int',
        'days_interval' => 'int',
        'validate' => 'bool'
    ];

    /**
      * Array of property to format mappings. Used for (de)serialization
      *
      * @var string[]
      * @phpstan-var array<string, string|null>
      * @psalm-var array<string, string|null>
      */
    protected static $openAPIFormats = [
        'index_id' => null,
        'min_popularity' => null,
        'min_hits' => null,
        'min_letters' => null,
        'days_interval' => null,
        'validate' => null
    ];

    /**
      * Array of nullable properties. Used for (de)serialization
      *
      * @var boolean[]
      */
    protected static array $openAPINullables = [
        'index_id' => false,
        'min_popularity' => false,
        'min_hits' => false,
        'min_letters' => false,
        'days_interval' => false,
        'validate' => false
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
        'index_id' => 'indexId',
        'min_popularity' => 'minPopularity',
        'min_hits' => 'minHits',
        'min_letters' => 'minLetters',
        'days_interval' => 'daysInterval',
        'validate' => 'validate'
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'index_id' => 'setIndexId',
        'min_popularity' => 'setMinPopularity',
        'min_hits' => 'setMinHits',
        'min_letters' => 'setMinLetters',
        'days_interval' => 'setDaysInterval',
        'validate' => 'setValidate'
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'index_id' => 'getIndexId',
        'min_popularity' => 'getMinPopularity',
        'min_hits' => 'getMinHits',
        'min_letters' => 'getMinLetters',
        'days_interval' => 'getDaysInterval',
        'validate' => 'getValidate'
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
        $this->setIfExists('index_id', $data ?? [], null);
        $this->setIfExists('min_popularity', $data ?? [], 5);
        $this->setIfExists('min_hits', $data ?? [], 1);
        $this->setIfExists('min_letters', $data ?? [], 2);
        $this->setIfExists('days_interval', $data ?? [], 30);
        $this->setIfExists('validate', $data ?? [], false);
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

        if ($this->container['index_id'] === null) {
            $invalidProperties[] = "'index_id' can't be null";
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
     * Gets index_id
     *
     * @return string
     */
    public function getIndexId()
    {
        return $this->container['index_id'];
    }

    /**
     * Sets index_id
     *
     * @param string $index_id ID of the index
     *
     * @return self
     */
    public function setIndexId($index_id)
    {
        if (is_null($index_id)) {
            throw new \InvalidArgumentException('non-nullable index_id cannot be null');
        }
        $this->container['index_id'] = $index_id;

        return $this;
    }

    /**
     * Gets min_popularity
     *
     * @return int|null
     */
    public function getMinPopularity()
    {
        return $this->container['min_popularity'];
    }

    /**
     * Sets min_popularity
     *
     * @param int|null $min_popularity Minimum popularity of a query to be used as a suggestion
     *
     * @return self
     */
    public function setMinPopularity($min_popularity)
    {
        if (is_null($min_popularity)) {
            throw new \InvalidArgumentException('non-nullable min_popularity cannot be null');
        }
        $this->container['min_popularity'] = $min_popularity;

        return $this;
    }

    /**
     * Gets min_hits
     *
     * @return int|null
     */
    public function getMinHits()
    {
        return $this->container['min_hits'];
    }

    /**
     * Sets min_hits
     *
     * @param int|null $min_hits Minimum search hits of a query to be used as a suggestion
     *
     * @return self
     */
    public function setMinHits($min_hits)
    {
        if (is_null($min_hits)) {
            throw new \InvalidArgumentException('non-nullable min_hits cannot be null');
        }
        $this->container['min_hits'] = $min_hits;

        return $this;
    }

    /**
     * Gets min_letters
     *
     * @return int|null
     */
    public function getMinLetters()
    {
        return $this->container['min_letters'];
    }

    /**
     * Sets min_letters
     *
     * @param int|null $min_letters Minimum required number of letters for a suggestion to remain
     *
     * @return self
     */
    public function setMinLetters($min_letters)
    {
        if (is_null($min_letters)) {
            throw new \InvalidArgumentException('non-nullable min_letters cannot be null');
        }
        $this->container['min_letters'] = $min_letters;

        return $this;
    }

    /**
     * Gets days_interval
     *
     * @return int|null
     */
    public function getDaysInterval()
    {
        return $this->container['days_interval'];
    }

    /**
     * Sets days_interval
     *
     * @param int|null $days_interval Suggestions will be created from search statistics from last `daysInterval` days
     *
     * @return self
     */
    public function setDaysInterval($days_interval)
    {
        if (is_null($days_interval)) {
            throw new \InvalidArgumentException('non-nullable days_interval cannot be null');
        }
        $this->container['days_interval'] = $days_interval;

        return $this;
    }

    /**
     * Gets validate
     *
     * @return bool|null
     */
    public function getValidate()
    {
        return $this->container['validate'];
    }

    /**
     * Sets validate
     *
     * @param bool|null $validate When `true`, the suggestions presence in searchable attributes is verified
     *
     * @return self
     */
    public function setValidate($validate)
    {
        if (is_null($validate)) {
            throw new \InvalidArgumentException('non-nullable validate cannot be null');
        }
        $this->container['validate'] = $validate;

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

