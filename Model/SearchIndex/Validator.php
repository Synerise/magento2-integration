<?php

namespace Synerise\Integration\Model\SearchIndex;

use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Validator\AbstractValidator;
use Synerise\CatalogsApiClient\ApiException;
use Synerise\Integration\Model\SearchIndex;
use Synerise\Integration\Search\Attributes\Config;
use Synerise\Integration\SyneriseApi\Catalogs\AttributeValidatorFactory;
use Synerise\Integration\SyneriseApi\Sender\Catalog;

class Validator extends AbstractValidator
{
    public const UNAVAILABLE_ATTRIBUTES_MESSAGE = 'Missing required attributes (%1). Please make sure that catalog (%2) contains them.';

    public const CATALOG_NOT_FOUND = "Catalog not found %1.";

    public const CATALOG_NOT_FOUND_FOR_INDEX = "Catalog (%1) not found  for index: %2 (%3).";

    /**
     * @var AttributeValidatorFactory
     */
    private $attributeValidatorFactory;

    /**
     * @var Catalog
     */
    private $catalog;

    public function __construct(
        AttributeValidatorFactory $attributeValidatorFactory,
        Catalog $catalog
    ) {
        $this->attributeValidatorFactory = $attributeValidatorFactory;
        $this->catalog = $catalog;
    }

    /**
     * Validate search index
     *
     * @param SearchIndex $searchIndex
     * @return bool
     * @throws ApiException
     * @throws ValidatorException
     * @throws \Synerise\ApiClient\ApiException
     */
    public function isValid($searchIndex)
    {
        $messages = [];

        if ($error = $this->validateCatalog($searchIndex)) {
            $messages[] = $error;
        } else {
            $unavailable = $this->validateRequiredAttributes($searchIndex);
            if (!empty($unavailable)) {
                $messages[] = __(
                    self::UNAVAILABLE_ATTRIBUTES_MESSAGE,
                    implode(', ', $unavailable),
                    $searchIndex->getItemsCatalogId()
                );
            }
        }

        $this->_addMessages($messages);

        return empty($messages);
    }

    /**
     * Validate required attributes
     *
     * @param $searchIndex
     * @return array
     */
    protected function validateRequiredAttributes($searchIndex): array
    {
        $unavailable = [];

        $attributeValidator = $this->attributeValidatorFactory->create(
            $searchIndex->getItemsCatalogId(),
            $searchIndex->getStoreId()
        );

        foreach (Config::REQUIRED_DISPLAYABLE as $attributeCode) {
            if (!$attributeValidator->isValid($attributeCode)) {
                $unavailable[] = $attributeCode;
            }
        }

        foreach (Config::REQUIRED_SEARCHABLE as $attributeCode) {
            if (!$attributeValidator->isValid($attributeCode)) {
                $unavailable[] = $attributeCode;
            }
        }

        foreach (Config::REQUIRED_FACETABLE as $type => $attributeCodes) {
            foreach ($attributeCodes as $attributeCode) {
                if (!$attributeValidator->isValid($attributeCode)) {
                    $unavailable[] = $attributeCode;
                }
            }
        }

        foreach (Config::REQUIRED_FILTERABLE as $type => $attributeCodes) {
            foreach ($attributeCodes as $attributeCode) {
                if (!$attributeValidator->isValid($attributeCode)) {
                    $unavailable[] = $attributeCode;
                }
            }
        }

        return $unavailable;
    }

    /**
     * Validate catalog's existence
     *
     * @param SearchIndex $searchIndex
     * @return \Magento\Framework\Phrase|null
     * @throws ApiException
     * @throws \Magento\Framework\Exception\ValidatorException
     * @throws \Synerise\ApiClient\ApiException
     */
    protected function validateCatalog(SearchIndex $searchIndex)
    {
        try {
            $this->catalog->getCatalogById($searchIndex->getStoreId(), $searchIndex->getItemsCatalogId());
            return null;
        } catch (ApiException $e) {
            if ($e->getCode() == 404) {
                if ($searchIndex->getIndexId()) {
                    return __(
                        self::CATALOG_NOT_FOUND_FOR_INDEX,
                        $searchIndex->getItemsCatalogId(),
                        $searchIndex->getIndexName(),
                        $searchIndex->getIndexId()
                    );
                }

                return __(
                    self::CATALOG_NOT_FOUND,
                    $searchIndex->getItemsCatalogId(),
                    $searchIndex->getIndexId()
                );
            }

            throw $e;
        }
    }
}