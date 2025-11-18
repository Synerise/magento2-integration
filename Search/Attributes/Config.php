<?php

namespace Synerise\Integration\Search\Attributes;

class Config
{
    public const REQUIRED_DISPLAYABLE = [
        'entity_id'
    ];

    public const REQUIRED_SEARCHABLE = [
        'name'
    ];

    public const REQUIRED_FACETABLE = [
        'text' => [
            'category_ids'
        ],
        'range' => [
        ]
    ];

    public const REQUIRED_FILTERABLE = [
        'text' => [
            'entity_id',
            'is_salable',
            'visibility'
        ],
        'range' => [
            'deleted'
        ]
    ];

    /**
     * @var Config\Data
     */
    protected $dataStorage;

    /**
     * @param Config\Data $dataStorage
     */
    public function __construct(Config\Data $dataStorage)
    {
        $this->dataStorage = $dataStorage;
    }

    /**
     * @return array
     */
    public function getFieldIds(): array
    {
        return $this->dataStorage->get('field_id', []);
    }

    /**
     * Get all filterable attributes
     *
     * @return array
     */
    public function getAllFilterable(): array
    {
        return array_merge($this->getFilterableInSearch(), $this->getFilterableInListing());
    }

    /**
     * Get attributes filterable in category listing
     *
     * @return array
     */
    public function getFilterableInListing(): array
    {
        return $this->dataStorage->get('filterable_in_listing', []);
    }

    /**
     * Get attributes filterable in search
     *
     * @return array
     */
    public function getFilterableInSearch(): array
    {
        return $this->dataStorage->get('filterable_in_search', []);
    }

    /**
     * Get required for filters
     *
     * @return array
     */
    public function getFilterableRequired(): array
    {
        return $this->dataStorage->get('filterable_required', []);
    }

    /**
     * Get required for filters
     *
     * @return array
     */
    public function getFacetableRequired(): array
    {
        return $this->dataStorage->get('facetable_required', []);
    }

    /**
     * Get attributes used for search
     *
     * @return array
     */
    public function getSearchable(): array
    {
        return $this->dataStorage->get('searchable', []);
    }

    /**
     * Get attributes used for sorting
     *
     * @return array
     */
    public function getSortable(): array
    {
        return $this->dataStorage->get('sortable', []);
    }

    /**
     * Get configured field format id
     *
     * @return string
     */
    public function getFieldFormatId(): string
    {
        return $this->dataStorage->get('field_format_id', 1);
    }

    /**
     * Get frontend labels
     *
     * @return string[]
     */
    public function getFrontendLabels(): array
    {
        return $this->dataStorage->get('frontend_label', []);
    }

    /**
     * Get mapped field name
     *
     * @param string $code
     * @return string|null
     */
    public function getMappedFieldName(string $code): ?string
    {
        return $this->dataStorage->get('field_id/'.$code);
    }
}
