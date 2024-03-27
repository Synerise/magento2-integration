<?php
namespace Synerise\Integration\SyneriseApi\Catalogs\Config;

use Magento\Framework\Config\ReaderInterface;
use Magento\Framework\Exception\ValidatorException;
use Synerise\ApiClient\ApiException;
use Synerise\CatalogsApiClient\ApiException as CatalogsApiException;
use Synerise\CatalogsApiClient\Model\Bag;
use Synerise\Integration\Model\Synchronization\Config;
use Synerise\Integration\SyneriseApi\Sender\Catalog;

class Reader implements ReaderInterface
{
    public const CATALOG_NAME_FORMAT = 'store-%s';

    /**
     * @var Config
     */
    protected $synchronization;

    /**
     * @var Catalog
     */
    private $catalog;

    /**
     * @param Config $synchronization
     * @param Catalog $catalog
     */
    public function __construct(
        Config $synchronization,
        Catalog $catalog
    ) {
        $this->synchronization = $synchronization;
        $this->catalog = $catalog;
    }

    /**
     * Read configuration
     *
     * @param mixed $scope
     * @return array
     */
    public function read($scope = null): array
    {
        $output = [];
        if ($this->synchronization->isModelEnabled('product')) {
            foreach ($this->synchronization->getConfiguredStores() as $storeId) {
                if ($scope == $storeId) {
                    $catalog = $this->getOrAddCatalogByStoreId($storeId);
                    $output[$scope] = $catalog ? $catalog->getId() : null;
                    break;
                }
            }
        }
        return $output;
    }

    /**
     * Get or add catalog
     *
     * @param int $storeId
     * @return Bag|null
     */
    public function getOrAddCatalogByStoreId(int $storeId): ?Bag
    {
        $catalogName = $this->getCatalogNameByStoreId($storeId);
        try {
            return $this->catalog->getCatalog($storeId, $catalogName) ?:
                $this->catalog->addCatalog($storeId, $catalogName);
        } catch (ValidatorException|ApiException|CatalogsApiException $e) {
            return null;
        }
    }

    /**
     * Get catalog name by store ID
     *
     * @param int $storeId
     * @return string
     */
    public function getCatalogNameByStoreId(int $storeId): string
    {
        return sprintf(self::CATALOG_NAME_FORMAT, $storeId);
    }
}
