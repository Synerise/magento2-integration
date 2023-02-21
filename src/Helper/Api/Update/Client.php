<?php

namespace Synerise\Integration\Helper\Api\Update;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Model\Customer;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;
use Synerise\ApiClient\Model\InBodyClientSex;
use Synerise\Integration\Helper\Api\Identity;
use Synerise\Integration\Model\Config\Source\Customers\Attributes;

class Client
{
    const UPDATE_GENDER = [
        1 => InBodyClientSex::MALE,
        2 => InBodyClientSex::FEMALE,
        3 => InBodyClientSex::NOT_SPECIFIED
    ];

    const XML_PATH_CUSTOMERS_ATTRIBUTES = 'synerise/customer/attributes';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var AddressRepositoryInterface
     */
    protected $addressRepository;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        AddressRepositoryInterface $addressRepository
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->addressRepository = $addressRepository;
    }

    /**
     * @param Customer|\Magento\Customer\Model\Data\Customer $customer
     * @param string|null $uuid
     * @param int|null $storeId
     * @return CreateaClientinCRMRequest
     */
    public function prepareCreateClientRequest($customer, ?string $uuid = null, ?int $storeId = null): CreateaClientinCRMRequest
    {
        $params = [
            'custom_id' => $customer->getId(),
            'email' => $customer->getEmail(),
            'uuid' => $uuid ?: Identity::generateUuidByEmail($customer->getEmail()),
            'first_name' => $customer->getFirstname(),
            'last_name' => $customer->getLastname()
        ];

        if (is_a($customer, 'Magento\Customer\Model\Data\Customer')) {
            $data = $customer->__toArray();
        } else {
            $data = (array) $customer->getData();
        }

        $selectedAttributes = $this->getAttributes($storeId);
        foreach ($selectedAttributes as $attribute) {
            if (!isset($data[$attribute])) {
                continue;
            }

            switch ($attribute) {
                case 'default_billing':
                    $defaultAddress = $this->getAddressIfAvailable($this->valOrNull($data['default_billing']));
                    if ($defaultAddress) {
                        $params['phone'] = $this->valOrNull($defaultAddress->getTelephone());
                        $params['city'] = $this->valOrNull($defaultAddress->getCity());
                        $street = $defaultAddress->getStreet();
                        $params['address'] = is_array($street) ? implode(" ", $street) : $this->valOrNull($street);
                        $params['zip_code'] = $this->valOrNull($defaultAddress->getPostcode());
                        $params['province'] = $this->valOrNull($defaultAddress->getRegion()->getRegion());
                        $params['country_code'] = $this->valOrNull($defaultAddress->getCountryId());
                        $params['company'] = $this->valOrNull($defaultAddress->getCompany());
                    }
                    break;
                case 'dob':
                    $params['birth_date'] = !empty($data['dob']) ? substr($data['dob'], 0, 10) : null;
                    break;
                case 'gender':
                    $params['sex'] = self::UPDATE_GENDER[$data['gender']] ?? null;
                    break;
                case 'display_name':
                case 'avatar_url':
                    $params[$attribute] = $this->valOrNull($data[$attribute]);
                    break;
                default:
                    if (!empty($data[$attribute])) {
                        $params['attributes'][$attribute] = $data[$attribute];
                    }
            }
        }

        return new CreateaClientinCRMRequest($params);
    }

    /**
     * @param int|null $storeId
     * @return string[]|false
     */
    public function getAttributes(int $storeId = null)
    {
        $attributes = $this->scopeConfig->getValue(
            self::XML_PATH_CUSTOMERS_ATTRIBUTES,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $attributes ? explode(',', $attributes) : [];
    }

    /**
     * @param int $storeId
     * @return array|null
     */
    public function getAttributesToSelect(int $storeId): ?array
    {
        $attributes = $this->getAttributes($storeId);
        return array_merge(
            $attributes,
            Attributes::REQUIRED
        );
    }

    /**
     * @param int $addressId
     * @return AddressInterface|null
     */
    protected function getAddressIfAvailable(int $addressId): ?AddressInterface
    {
        try {
            return $addressId ? $this->addressRepository->getById($addressId) : null;
        } catch (LocalizedException $e) {
            return null;
        }
    }

    /**
     * @param mixed $val
     * @return mixed
     */
    protected function valOrNull($val)
    {
        if(empty($val)){
            return null;
        }

        return empty(trim($val)) ? null : $val;
    }
}