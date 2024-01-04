<?php
namespace Synerise\Integration\SyneriseApi\Catalogs\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Config\ReaderInterface;
use Magento\Framework\Exception\ValidatorException;
use Synerise\ApiClient\ApiException;
use Synerise\CatalogsApiClient\Model\Bag;
use Synerise\Integration\Helper\Synchronization;
use Synerise\Integration\SyneriseApi\Sender\Catalog;

class Reader implements ReaderInterface
{
    public const CATALOG_NAME_FORMAT = 'store-%s';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var WriterInterface
     */
    protected $configWriter;

    /**
     * @var Synchronization
     */
    protected $synchronization;

    /**
     * @var Catalog
     */
    private $catalog;

    /**
     * @param $scopeConfig
     * @param $configWriter
     * @param Synchronization $synchronization
     * @param Catalog $catalog
     */
    public function __construct(
        $scopeConfig,
        $configWriter,
        Synchronization $synchronization,
        Catalog $catalog
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
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
        foreach ($this->synchronization->getEnabledStores() as $storeId) {
            $catalog = $this->getOrAddCatalogByStoreId($storeId);
            $output[$storeId] = $catalog ? $catalog->getId() : null;
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
