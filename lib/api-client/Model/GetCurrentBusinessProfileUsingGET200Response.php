<?php
/**
 * GetCurrentBusinessProfileUsingGET200Response
 *
 * PHP version 7.3
 *
 * @category Class
 * @package  Synerise\ApiClient
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 */

/**
 * Auth-api API Reference
 *
 * Api Documentation
 *
 * The version of the OpenAPI document: 1.0
 * Generated by: https://openapi-generator.tech
 * OpenAPI Generator version: 6.0.0-SNAPSHOT
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
 * GetCurrentBusinessProfileUsingGET200Response Class Doc Comment
 *
 * @category Class
 * @package  Synerise\ApiClient
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 * @implements \ArrayAccess<TKey, TValue>
 * @template TKey int|null
 * @template TValue mixed|null
 */
class GetCurrentBusinessProfileUsingGET200Response implements ModelInterface, ArrayAccess, \JsonSerializable
{
    public const DISCRIMINATOR = null;

    /**
      * The original name of the model.
      *
      * @var string
      */
    protected static $openAPIModelName = 'getCurrentBusinessProfileUsingGET_200_response';

    /**
      * Array of property to type mappings. Used for (de)serialization
      *
      * @var string[]
      */
    protected static $openAPITypes = [
        'business_profile_guid' => 'string',
        'logo' => 'string',
        'name' => 'string',
        'id' => 'int',
        'created' => '\DateTime',
        'subdomain' => 'string',
        'ip_restricted' => 'bool',
        'mfa_required' => 'bool'
    ];

    /**
      * Array of property to format mappings. Used for (de)serialization
      *
      * @var string[]
      * @phpstan-var array<string, string|null>
      * @psalm-var array<string, string|null>
      */
    protected static $openAPIFormats = [
        'business_profile_guid' => null,
        'logo' => null,
        'name' => null,
        'id' => null,
        'created' => 'date-time',
        'subdomain' => null,
        'ip_restricted' => null,
        'mfa_required' => null
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
        'business_profile_guid' => 'businessProfileGuid',
        'logo' => 'logo',
        'name' => 'name',
        'id' => 'id',
        'created' => 'created',
        'subdomain' => 'subdomain',
        'ip_restricted' => 'ipRestricted',
        'mfa_required' => 'mfaRequired'
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'business_profile_guid' => 'setBusinessProfileGuid',
        'logo' => 'setLogo',
        'name' => 'setName',
        'id' => 'setId',
        'created' => 'setCreated',
        'subdomain' => 'setSubdomain',
        'ip_restricted' => 'setIpRestricted',
        'mfa_required' => 'setMfaRequired'
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'business_profile_guid' => 'getBusinessProfileGuid',
        'logo' => 'getLogo',
        'name' => 'getName',
        'id' => 'getId',
        'created' => 'getCreated',
        'subdomain' => 'getSubdomain',
        'ip_restricted' => 'getIpRestricted',
        'mfa_required' => 'getMfaRequired'
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
        $this->container['business_profile_guid'] = $data['business_profile_guid'] ?? null;
        $this->container['logo'] = $data['logo'] ?? null;
        $this->container['name'] = $data['name'] ?? null;
        $this->container['id'] = $data['id'] ?? null;
        $this->container['created'] = $data['created'] ?? null;
        $this->container['subdomain'] = $data['subdomain'] ?? null;
        $this->container['ip_restricted'] = $data['ip_restricted'] ?? null;
        $this->container['mfa_required'] = $data['mfa_required'] ?? null;
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
     * Gets business_profile_guid
     *
     * @return string|null
     */
    public function getBusinessProfileGuid()
    {
        return $this->container['business_profile_guid'];
    }

    /**
     * Sets business_profile_guid
     *
     * @param string|null $business_profile_guid UUID of the business profile
     *
     * @return self
     */
    public function setBusinessProfileGuid($business_profile_guid)
    {
        $this->container['business_profile_guid'] = $business_profile_guid;

        return $this;
    }

    /**
     * Gets logo
     *
     * @return string|null
     */
    public function getLogo()
    {
        return $this->container['logo'];
    }

    /**
     * Sets logo
     *
     * @param string|null $logo URL of the logo
     *
     * @return self
     */
    public function setLogo($logo)
    {
        $this->container['logo'] = $logo;

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
     * @param string|null $name Name of the business profile
     *
     * @return self
     */
    public function setName($name)
    {
        $this->container['name'] = $name;

        return $this;
    }

    /**
     * Gets id
     *
     * @return int|null
     */
    public function getId()
    {
        return $this->container['id'];
    }

    /**
     * Sets id
     *
     * @param int|null $id ID of the business profile
     *
     * @return self
     */
    public function setId($id)
    {
        $this->container['id'] = $id;

        return $this;
    }

    /**
     * Gets created
     *
     * @return \DateTime|null
     */
    public function getCreated()
    {
        return $this->container['created'];
    }

    /**
     * Sets created
     *
     * @param \DateTime|null $created Creation date
     *
     * @return self
     */
    public function setCreated($created)
    {
        $this->container['created'] = $created;

        return $this;
    }

    /**
     * Gets subdomain
     *
     * @return string|null
     */
    public function getSubdomain()
    {
        return $this->container['subdomain'];
    }

    /**
     * Sets subdomain
     *
     * @param string|null $subdomain Sub-domain of the profile
     *
     * @return self
     */
    public function setSubdomain($subdomain)
    {
        $this->container['subdomain'] = $subdomain;

        return $this;
    }

    /**
     * Gets ip_restricted
     *
     * @return bool|null
     */
    public function getIpRestricted()
    {
        return $this->container['ip_restricted'];
    }

    /**
     * Sets ip_restricted
     *
     * @param bool|null $ip_restricted Informs if the profile has IP access restrictions.
     *
     * @return self
     */
    public function setIpRestricted($ip_restricted)
    {
        $this->container['ip_restricted'] = $ip_restricted;

        return $this;
    }

    /**
     * Gets mfa_required
     *
     * @return bool|null
     */
    public function getMfaRequired()
    {
        return $this->container['mfa_required'];
    }

    /**
     * Sets mfa_required
     *
     * @param bool|null $mfa_required Informs if the profile is only accessible to users with multi-factor authentication enabled.
     *
     * @return self
     */
    public function setMfaRequired($mfa_required)
    {
        $this->container['mfa_required'] = $mfa_required;

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


