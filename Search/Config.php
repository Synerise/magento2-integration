<?php

namespace Synerise\Integration\Search;

use Synerise\Integration\Search\Config\Data;
use Synerise\Integration\Search\Config\DataFactory;

class Config
{
    /**
     * @var Data
     */
    protected $dataStorage;

    /**
     * @var int
     */
    protected $storeId;

    /**
     * @param DataFactory $dataFactory
     * @param int $storeId
     */
    public function __construct(DataFactory $dataFactory, int $storeId)
    {
        $this->dataStorage = $dataFactory->create($storeId);
        $this->storeId = $storeId;
    }

    /**
     * Is products autocomplete enabled
     *
     * @return bool
     */
    public function isProductsAutocompleteEnabled(): bool
    {
        return $this->dataStorage->get('productsAutocompleteEnabled', false);
    }

    /**
     * Get products autocomplete limit
     *
     * @return int
     */
    public function getProductsAutocompleteLimit(): int
    {
        return $this->dataStorage->get('productsAutocompleteLimit', 8);
    }

    /**
     * Get search index
     *
     * @return string|null
     */
    public function getSearchIndex(): ?string
    {
        return $this->dataStorage->get('searchIndex', null);
    }

    /**
     * Is suggestions autocomplete enabled
     *
     * @return bool
     */
    public function isSuggestionsAutocompleteEnabled(): bool
    {
        return $this->dataStorage->get('suggestionsAutocompleteEnabled', false);
    }

    /**
     * Get suggestions autocomplete limit
     *
     * @return int
     */
    public function getSuggestionsAutocompleteLimit(): int
    {
        return $this->dataStorage->get('suggestionsAutocompleteLimit', 8);
    }

    /**
     * Get suggestions index
     *
     * @return string|null
     */
    public function getSuggestionsIndex(): ?string
    {
        return $this->dataStorage->get('suggestionsIndex', null);
    }
}
