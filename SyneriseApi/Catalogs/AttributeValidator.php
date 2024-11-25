<?php

namespace Synerise\Integration\SyneriseApi\Catalogs;

use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Validator\AbstractValidator;
use Synerise\CatalogsApiClient\Api\BagsApi;
use Synerise\Integration\Model\Workspace\ConfigFactory as WorkspaceConfigFactory;
use Synerise\Integration\SyneriseApi\ConfigFactory as ApiConfigFactory;
use Synerise\Integration\SyneriseApi\InstanceFactory as ApiInstanceFactory;
use Synerise\ItemsSearchConfigApiClient\ApiException;

class AttributeValidator extends AbstractValidator
{
    /**
     * @var ApiConfigFactory
     */
    protected $apiConfigFactory;

    /**
     * @var ApiInstanceFactory
     */
    protected $apiInstanceFactory;

    /**
     * @var WorkspaceConfigFactory
     */
    protected $workspaceConfigFactory;

    /**
     * @var int|null
     */
    protected $catalogId;

    /**
     * @var int|null
     */
    protected $storeId;
    /**
     * @var string[]
     */
    protected $availableAttributes;
    /**
     * @var string[]
     */
    protected $unavailableAttributes = [];

    public function __construct(
        ApiConfigFactory $apiConfigFactory,
        ApiInstanceFactory $apiInstanceFactory,
        WorkspaceConfigFactory $workspaceConfigFactory,
        ?int $catalogId = null,
        ?int $storeId = null
    ) {
        $this->apiConfigFactory = $apiConfigFactory;
        $this->apiInstanceFactory = $apiInstanceFactory;
        $this->workspaceConfigFactory = $workspaceConfigFactory;
        $this->catalogId = $catalogId;
        $this->storeId = $storeId;

        $this->availableAttributes = $this->getCatalogsApiInstance()
            ->getKeysByBag($catalogId)->getData() ?? [];
    }

    public function getValid(array $candidates)
    {
        $valid = [];
        foreach ($candidates as $fieldName) {
            if ($this->isValid($this->getAttributeCodeFromField($fieldName))) {
                $valid[] = $fieldName;
            }
        }
        return $valid;
    }

    /**
     * Check if attribute is present in catalog
     *
     * @param string $attributeCode
     * @return bool
     */
    public function isValid($attributeCode)
    {
        if (!in_array($attributeCode, $this->unavailableAttributes)) {
            if (in_array($attributeCode, $this->availableAttributes)) {
                return true;
            } else {
                $this->unavailableAttributes[] = $attributeCode;
            }
        }

        return false;
    }

    /**
     * @return int|null
     */
    public function getCatalogId(): ?int
    {
        return $this->catalogId;
    }

    /**
     * @return int|null
     */
    public function getStoreId(): ?int
    {
        return $this->storeId;
    }

    /**
     * @return string[]
     */
    public function getAvailableAttributes(): array
    {
        return $this->availableAttributes;
    }

    /**
     * @return string[]
     */
    public function getUnavailableAttributes(): array
    {
        return $this->unavailableAttributes;
    }

    /**
     * Get attribute code without suffix
     *
     * @param $fieldName
     * @return array|string|string[]|null
     */
    protected function getAttributeCodeFromField($fieldName)
    {
        return preg_replace('%\.(id|label)$%', '', $fieldName);
    }

    /**
     * Get AI Search Api instance
     *
     * @return BagsApi
     * @throws ValidatorException
     * @throws ApiException
     */
    protected function getCatalogsApiInstance(): BagsApi
    {
        return $this->apiInstanceFactory->createApiInstance(
            'catalogs',
            $this->apiConfigFactory->create($this->getStoreId()),
            $this->workspaceConfigFactory->create($this->getStoreId())
        );
    }
}