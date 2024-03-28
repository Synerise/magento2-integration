<?php

namespace Synerise\Integration\SyneriseApi\Mapper\Data;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;
use Synerise\ApiClient\Model\InBodyClientSex;
use Synerise\Integration\Model\Config\Source\Customers\Attributes;
use Synerise\Integration\SyneriseApi\Sender\Data\Customer;

class CustomerCRUD
{
    public const MAPPING_GENDER = [
        1 => InBodyClientSex::MALE,
        2 => InBodyClientSex::FEMALE,
        3 => InBodyClientSex::NOT_SPECIFIED
    ];

    /**
     * @var AddressRepositoryInterface
     */
    protected $addressRepository;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param AddressRepositoryInterface $addressRepository
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        AddressRepositoryInterface $addressRepository,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->addressRepository = $addressRepository;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Prepare customer params
     *
     * @param Customer|\Magento\Customer\Model\Data\Customer $customer
     * @param int|null $storeId
     * @return CreateaClientinCRMRequest
     */
    public function prepareRequest($customer, ?int $storeId = null): CreateaClientinCRMRequest
    {
        $params = [
            'custom_id' => $customer->getId(),
            'email' => $customer->getEmail(),
            'first_name' => $customer->getFirstname(),
            'last_name' => $customer->getLastname()
        ];

        if (is_a($customer, \Magento\Customer\Model\Data\Customer::class)) {
            /** @var \Magento\Customer\Model\Data\Customer $customer */
            $data = $customer->__toArray();
        } else {
            /** @var \Magento\Customer\Model\Customer $customer */
            $data = (array) $customer->getData();
        }

        $selectedAttributes = $this->getEnabledAttributes($storeId);
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
                        $params['address'] = $this->valOrNull(is_array($street) ? implode(' ', $street) : $street);
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
                    $params['sex'] = self::MAPPING_GENDER[$data['gender']] ?? null;
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
     * Get enabled attributes
     *
     * @param int|null $storeId
     * @return array|false|string[]
     */
    public function getEnabledAttributes(?int $storeId = null)
    {
        $attributes = $this->scopeConfig->getValue(
            Attributes::XML_PATH_CUSTOMER_ATTRIBUTES,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $attributes ? explode(',', $attributes) : [];
    }

    /**
     * Get address if available
     *
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
     * Return value or null
     *
     * @param string|null $val
     * @return string|null
     */
    protected function valOrNull(?string $val): ?string
    {
        if (empty($val)) {
            return null;
        }

        return empty(trim($val)) ? null : $val;
    }
}
