<?php
/**
 * InResponseClientDetails
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
 * InResponseClientDetails Class Doc Comment
 *
 * @category Class
 * @package  Synerise\ApiClient
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 * @implements \ArrayAccess<TKey, TValue>
 * @template TKey int|null
 * @template TValue mixed|null  
 */
class InResponseClientDetails implements ModelInterface, ArrayAccess, \JsonSerializable
{
    public const DISCRIMINATOR = null;

    /**
      * The original name of the model.
      *
      * @var string
      */
    protected static $openAPIModelName = 'inResponseClientDetails';

    /**
      * Array of property to type mappings. Used for (de)serialization
      *
      * @var string[]
      */
    protected static $openAPITypes = [
        'client_id' => 'int',
        'email' => 'string',
        'phone' => 'string',
        'custom_id' => 'string',
        'uuid' => 'string',
        'first_name' => 'string',
        'last_name' => 'string',
        'display_name' => 'string',
        'company' => 'string',
        'address' => 'string',
        'city' => 'string',
        'province' => 'string',
        'zip_code' => 'string',
        'country_code' => 'string',
        'birth_date' => 'string',
        'last_activity_date' => 'string',
        'sex' => '\Synerise\ApiClient\Model\InBodyClientSex',
        'avatar_url' => 'string',
        'anonymous' => 'bool',
        'agreements' => '\Synerise\ApiClient\Model\Agreements',
        'attributes' => '\Synerise\ApiClient\Model\Attributes',
        'tags' => 'string[]',
        'previous_clients' => 'string[]'
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
        'email' => null,
        'phone' => null,
        'custom_id' => null,
        'uuid' => null,
        'first_name' => null,
        'last_name' => null,
        'display_name' => null,
        'company' => null,
        'address' => null,
        'city' => null,
        'province' => null,
        'zip_code' => null,
        'country_code' => null,
        'birth_date' => null,
        'last_activity_date' => null,
        'sex' => null,
        'avatar_url' => null,
        'anonymous' => null,
        'agreements' => null,
        'attributes' => null,
        'tags' => null,
        'previous_clients' => null
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
        'email' => 'email',
        'phone' => 'phone',
        'custom_id' => 'customId',
        'uuid' => 'uuid',
        'first_name' => 'firstName',
        'last_name' => 'lastName',
        'display_name' => 'displayName',
        'company' => 'company',
        'address' => 'address',
        'city' => 'city',
        'province' => 'province',
        'zip_code' => 'zipCode',
        'country_code' => 'countryCode',
        'birth_date' => 'birthDate',
        'last_activity_date' => 'lastActivityDate',
        'sex' => 'sex',
        'avatar_url' => 'avatarUrl',
        'anonymous' => 'anonymous',
        'agreements' => 'agreements',
        'attributes' => 'attributes',
        'tags' => 'tags',
        'previous_clients' => 'previousClients'
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'client_id' => 'setClientId',
        'email' => 'setEmail',
        'phone' => 'setPhone',
        'custom_id' => 'setCustomId',
        'uuid' => 'setUuid',
        'first_name' => 'setFirstName',
        'last_name' => 'setLastName',
        'display_name' => 'setDisplayName',
        'company' => 'setCompany',
        'address' => 'setAddress',
        'city' => 'setCity',
        'province' => 'setProvince',
        'zip_code' => 'setZipCode',
        'country_code' => 'setCountryCode',
        'birth_date' => 'setBirthDate',
        'last_activity_date' => 'setLastActivityDate',
        'sex' => 'setSex',
        'avatar_url' => 'setAvatarUrl',
        'anonymous' => 'setAnonymous',
        'agreements' => 'setAgreements',
        'attributes' => 'setAttributes',
        'tags' => 'setTags',
        'previous_clients' => 'setPreviousClients'
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'client_id' => 'getClientId',
        'email' => 'getEmail',
        'phone' => 'getPhone',
        'custom_id' => 'getCustomId',
        'uuid' => 'getUuid',
        'first_name' => 'getFirstName',
        'last_name' => 'getLastName',
        'display_name' => 'getDisplayName',
        'company' => 'getCompany',
        'address' => 'getAddress',
        'city' => 'getCity',
        'province' => 'getProvince',
        'zip_code' => 'getZipCode',
        'country_code' => 'getCountryCode',
        'birth_date' => 'getBirthDate',
        'last_activity_date' => 'getLastActivityDate',
        'sex' => 'getSex',
        'avatar_url' => 'getAvatarUrl',
        'anonymous' => 'getAnonymous',
        'agreements' => 'getAgreements',
        'attributes' => 'getAttributes',
        'tags' => 'getTags',
        'previous_clients' => 'getPreviousClients'
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
        $this->container['email'] = $data['email'] ?? null;
        $this->container['phone'] = $data['phone'] ?? null;
        $this->container['custom_id'] = $data['custom_id'] ?? null;
        $this->container['uuid'] = $data['uuid'] ?? null;
        $this->container['first_name'] = $data['first_name'] ?? null;
        $this->container['last_name'] = $data['last_name'] ?? null;
        $this->container['display_name'] = $data['display_name'] ?? null;
        $this->container['company'] = $data['company'] ?? null;
        $this->container['address'] = $data['address'] ?? null;
        $this->container['city'] = $data['city'] ?? null;
        $this->container['province'] = $data['province'] ?? null;
        $this->container['zip_code'] = $data['zip_code'] ?? null;
        $this->container['country_code'] = $data['country_code'] ?? null;
        $this->container['birth_date'] = $data['birth_date'] ?? null;
        $this->container['last_activity_date'] = $data['last_activity_date'] ?? null;
        $this->container['sex'] = $data['sex'] ?? null;
        $this->container['avatar_url'] = $data['avatar_url'] ?? null;
        $this->container['anonymous'] = $data['anonymous'] ?? null;
        $this->container['agreements'] = $data['agreements'] ?? null;
        $this->container['attributes'] = $data['attributes'] ?? null;
        $this->container['tags'] = $data['tags'] ?? null;
        $this->container['previous_clients'] = $data['previous_clients'] ?? null;
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
     * Gets email
     *
     * @return string|null
     */
    public function getEmail()
    {
        return $this->container['email'];
    }

    /**
     * Sets email
     *
     * @param string|null $email Client's e-mail address
     *
     * @return self
     */
    public function setEmail($email)
    {
        $this->container['email'] = $email;

        return $this;
    }

    /**
     * Gets phone
     *
     * @return string|null
     */
    public function getPhone()
    {
        return $this->container['phone'];
    }

    /**
     * Sets phone
     *
     * @param string|null $phone Client's phone number
     *
     * @return self
     */
    public function setPhone($phone)
    {
        $this->container['phone'] = $phone;

        return $this;
    }

    /**
     * Gets custom_id
     *
     * @return string|null
     */
    public function getCustomId()
    {
        return $this->container['custom_id'];
    }

    /**
     * Sets custom_id
     *
     * @param string|null $custom_id A custom ID for the Client
     *
     * @return self
     */
    public function setCustomId($custom_id)
    {
        $this->container['custom_id'] = $custom_id;

        return $this;
    }

    /**
     * Gets uuid
     *
     * @return string|null
     */
    public function getUuid()
    {
        return $this->container['uuid'];
    }

    /**
     * Sets uuid
     *
     * @param string|null $uuid UUID of the Client
     *
     * @return self
     */
    public function setUuid($uuid)
    {
        $this->container['uuid'] = $uuid;

        return $this;
    }

    /**
     * Gets first_name
     *
     * @return string|null
     */
    public function getFirstName()
    {
        return $this->container['first_name'];
    }

    /**
     * Sets first_name
     *
     * @param string|null $first_name Clients first name
     *
     * @return self
     */
    public function setFirstName($first_name)
    {
        $this->container['first_name'] = $first_name;

        return $this;
    }

    /**
     * Gets last_name
     *
     * @return string|null
     */
    public function getLastName()
    {
        return $this->container['last_name'];
    }

    /**
     * Sets last_name
     *
     * @param string|null $last_name Client's last name
     *
     * @return self
     */
    public function setLastName($last_name)
    {
        $this->container['last_name'] = $last_name;

        return $this;
    }

    /**
     * Gets display_name
     *
     * @return string|null
     */
    public function getDisplayName()
    {
        return $this->container['display_name'];
    }

    /**
     * Sets display_name
     *
     * @param string|null $display_name Currently unused
     *
     * @return self
     */
    public function setDisplayName($display_name)
    {
        $this->container['display_name'] = $display_name;

        return $this;
    }

    /**
     * Gets company
     *
     * @return string|null
     */
    public function getCompany()
    {
        return $this->container['company'];
    }

    /**
     * Sets company
     *
     * @param string|null $company Client's company
     *
     * @return self
     */
    public function setCompany($company)
    {
        $this->container['company'] = $company;

        return $this;
    }

    /**
     * Gets address
     *
     * @return string|null
     */
    public function getAddress()
    {
        return $this->container['address'];
    }

    /**
     * Sets address
     *
     * @param string|null $address Client's street address
     *
     * @return self
     */
    public function setAddress($address)
    {
        $this->container['address'] = $address;

        return $this;
    }

    /**
     * Gets city
     *
     * @return string|null
     */
    public function getCity()
    {
        return $this->container['city'];
    }

    /**
     * Sets city
     *
     * @param string|null $city Client's city of residence
     *
     * @return self
     */
    public function setCity($city)
    {
        $this->container['city'] = $city;

        return $this;
    }

    /**
     * Gets province
     *
     * @return string|null
     */
    public function getProvince()
    {
        return $this->container['province'];
    }

    /**
     * Sets province
     *
     * @param string|null $province Client's province of residence
     *
     * @return self
     */
    public function setProvince($province)
    {
        $this->container['province'] = $province;

        return $this;
    }

    /**
     * Gets zip_code
     *
     * @return string|null
     */
    public function getZipCode()
    {
        return $this->container['zip_code'];
    }

    /**
     * Sets zip_code
     *
     * @param string|null $zip_code Client's zip code
     *
     * @return self
     */
    public function setZipCode($zip_code)
    {
        $this->container['zip_code'] = $zip_code;

        return $this;
    }

    /**
     * Gets country_code
     *
     * @return string|null
     */
    public function getCountryCode()
    {
        return $this->container['country_code'];
    }

    /**
     * Sets country_code
     *
     * @param string|null $country_code Code of Client's country of residence in accordance with the ISO 3166 format
     *
     * @return self
     */
    public function setCountryCode($country_code)
    {
        $this->container['country_code'] = $country_code;

        return $this;
    }

    /**
     * Gets birth_date
     *
     * @return string|null
     */
    public function getBirthDate()
    {
        return $this->container['birth_date'];
    }

    /**
     * Sets birth_date
     *
     * @param string|null $birth_date Client's date of birth. Must be in `yyyy-mm-dd` format and later than `1900-01-01`.<br>**IMPORTANT**: Months and days must be zero-padded. For example: May 3, 1993 is `1993-05-03`.
     *
     * @return self
     */
    public function setBirthDate($birth_date)
    {
        $this->container['birth_date'] = $birth_date;

        return $this;
    }

    /**
     * Gets last_activity_date
     *
     * @return string|null
     */
    public function getLastActivityDate()
    {
        return $this->container['last_activity_date'];
    }

    /**
     * Sets last_activity_date
     *
     * @param string|null $last_activity_date Time of last Client activity
     *
     * @return self
     */
    public function setLastActivityDate($last_activity_date)
    {
        $this->container['last_activity_date'] = $last_activity_date;

        return $this;
    }

    /**
     * Gets sex
     *
     * @return \Synerise\ApiClient\Model\InBodyClientSex|null
     */
    public function getSex()
    {
        return $this->container['sex'];
    }

    /**
     * Sets sex
     *
     * @param \Synerise\ApiClient\Model\InBodyClientSex|null $sex sex
     *
     * @return self
     */
    public function setSex($sex)
    {
        $this->container['sex'] = $sex;

        return $this;
    }

    /**
     * Gets avatar_url
     *
     * @return string|null
     */
    public function getAvatarUrl()
    {
        return $this->container['avatar_url'];
    }

    /**
     * Sets avatar_url
     *
     * @param string|null $avatar_url URL of the Client's avatar picture
     *
     * @return self
     */
    public function setAvatarUrl($avatar_url)
    {
        $this->container['avatar_url'] = $avatar_url;

        return $this;
    }

    /**
     * Gets anonymous
     *
     * @return bool|null
     */
    public function getAnonymous()
    {
        return $this->container['anonymous'];
    }

    /**
     * Sets anonymous
     *
     * @param bool|null $anonymous Information if the Client is anonymous
     *
     * @return self
     */
    public function setAnonymous($anonymous)
    {
        $this->container['anonymous'] = $anonymous;

        return $this;
    }

    /**
     * Gets agreements
     *
     * @return \Synerise\ApiClient\Model\Agreements|null
     */
    public function getAgreements()
    {
        return $this->container['agreements'];
    }

    /**
     * Sets agreements
     *
     * @param \Synerise\ApiClient\Model\Agreements|null $agreements agreements
     *
     * @return self
     */
    public function setAgreements($agreements)
    {
        $this->container['agreements'] = $agreements;

        return $this;
    }

    /**
     * Gets attributes
     *
     * @return \Synerise\ApiClient\Model\Attributes|null
     */
    public function getAttributes()
    {
        return $this->container['attributes'];
    }

    /**
     * Sets attributes
     *
     * @param \Synerise\ApiClient\Model\Attributes|null $attributes attributes
     *
     * @return self
     */
    public function setAttributes($attributes)
    {
        $this->container['attributes'] = $attributes;

        return $this;
    }

    /**
     * Gets tags
     *
     * @return string[]|null
     */
    public function getTags()
    {
        return $this->container['tags'];
    }

    /**
     * Sets tags
     *
     * @param string[]|null $tags Tags can be used to group Client accounts.
     *
     * @return self
     */
    public function setTags($tags)
    {
        $this->container['tags'] = $tags;

        return $this;
    }

    /**
     * Gets previous_clients
     *
     * @return string[]|null
     */
    public function getPreviousClients()
    {
        return $this->container['previous_clients'];
    }

    /**
     * Sets previous_clients
     *
     * @param string[]|null $previous_clients Currently unused
     *
     * @return self
     */
    public function setPreviousClients($previous_clients)
    {
        $this->container['previous_clients'] = $previous_clients;

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


