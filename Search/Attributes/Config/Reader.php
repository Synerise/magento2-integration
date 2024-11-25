<?php
namespace Synerise\Integration\Search\Attributes\Config;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Config\ReaderInterface;
use Synerise\Integration\Model\Config\Source\Products\Attributes\Format;
use Synerise\Integration\Search\Attributes\Config;

class Reader implements ReaderInterface
{
    public const XML_PATH_PRODUCTS_LABELS_ENABLED = 'synerise/product/labels_enabled';

    /**
     * @var string[]
     */
    protected $fieldFormat;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var CollectionFactory
     */
    protected $attributeCollectionFactory;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        CollectionFactory $attributeCollectionFactory
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->attributeCollectionFactory = $attributeCollectionFactory;
    }

    /**
     * Read configuration
     *
     * @param mixed $scope
     * @return array
     */
    public function read($scope = null): array
    {
        $collection = $this->attributeCollectionFactory->create()
            ->addFieldToSelect([
                AttributeInterface::ATTRIBUTE_CODE,
                AttributeInterface::FRONTEND_INPUT,
                AttributeInterface::FRONTEND_LABEL
            ])
            ->addToIndexFilter(true);

        $data = [
            'field_format_id' => $this->getFieldFormatId()
        ];

        $data['field_id']['category_ids'] = 'category_ids';

        foreach ($collection as $attribute) {
            $code = $attribute->getAttributeCode();
            $fieldId = $this->getFormattedFieldName($attribute);
            $fieldLabel = $this->getFormattedFieldLabel($attribute);

            $data['field_id'][$code] = $fieldId;
            $data['frontend_label'][$code] = $attribute->getFrontendLabel();

            if ($attribute->getIsSearchable()) {
                $data['searchable'][$code] = $fieldLabel;
            }

            if ($attribute->getUsedForSortBy()) {
                $data['sortable'][$code] = $fieldLabel;
            }

            if ($attribute->getIsFilterable()) {
                $data['filterable_in_listing'][$code] = $fieldId;
            }

            if ($attribute->getIsFilterableInSearch()) {
                $data['filterable_in_search'][$code] = $fieldId;
            }
        }

        foreach (Config::REQUIRED_FILTERABLE as $type => $codes) {
            $data['filterable_required'][$type] = [];
            foreach ($codes as $code) {
                $data['filterable_required'][$type][] = $data['field_id'][$code] ?? $code;
            }
        }

        foreach (Config::REQUIRED_FACETABLE as $type => $codes) {
            $data['facetable_required'][$type] = [];
            foreach ($codes as $code) {
                $data['facetable_required'][$type][] = $data['field_id'][$code] ?? $code;
            }
        }

        return $data;
    }

    /**
     * Get formatted field name based on attribute frontend input type
     *
     * @param Attribute $attribute
     * @return mixed|string|null
     */
    protected function getFormattedFieldName(Attribute $attribute)
    {
        return !in_array($attribute->getFrontendInput(), ['select', 'multiselect', 'boolean']) ?
            $attribute->getAttributeCode() : $this->formatFieldName($attribute->getAttributeCode());
    }

    /**
     * Get formatted field label based on attribute frontend input type
     *
     * @param Attribute $attribute
     * @return mixed|string|null
     */
    protected function getFormattedFieldLabel(Attribute $attribute)
    {
        return !in_array($attribute->getFrontendInput(), ['select', 'multiselect', 'boolean']) ?
            $attribute->getAttributeCode() : $this->formatFieldLabel($attribute->getAttributeCode());
    }

    /**
     * Format field name by configured format
     *
     * @param $field
     * @return string
     */
    protected function formatFieldName($field): string
    {
        return sprintf($this->getFieldNameFormat(), $field);
    }

    /**
     * Format field label by configured format
     *
     * @param $field
     * @return string
     */
    protected function formatFieldLabel($field): string
    {
        return sprintf($this->getFieldLabelFormat(), $field);
    }

    /**
     * Get field name format
     *
     * @return string
     */
    protected function getFieldNameFormat(): string
    {
        if (!isset($this->fieldFormat['name'])) {
            if ($this->getFieldFormatId() == Format::OPTION_ID_AND_LABEL) {
                $this->fieldFormat['name'] = "%s.id";
            } else {
                $this->fieldFormat['name'] = "%s";
            }
        }

        return $this->fieldFormat['name'];
    }

    /**
     * Get field label format
     *
     * @return string
     */
    protected function getFieldLabelFormat(): string
    {
        if (!isset($this->fieldFormat['label'])) {
            if ($this->getFieldFormatId() == Format::OPTION_ID_AND_LABEL) {
                $this->fieldFormat['label'] = "%s.label";
            } else {
                $this->fieldFormat['label'] = "%s";
            }
        }

        return $this->fieldFormat['label'];
    }

    /**
     * Get configured field format id
     *
     * @return int
     */
    protected function getFieldFormatId(): int
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_PRODUCTS_LABELS_ENABLED
        );
    }
}
