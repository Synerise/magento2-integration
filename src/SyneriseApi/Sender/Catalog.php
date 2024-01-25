<?php

namespace Synerise\Integration\SyneriseApi\Sender;

use Magento\Framework\Exception\ValidatorException;
use Synerise\ApiClient\ApiException as DefaultApiException;
use Synerise\CatalogsApiClient\Api\BagsApi;
use Synerise\CatalogsApiClient\CatalogsApiException;
use Synerise\CatalogsApiClient\Model\AddBag;
use Synerise\CatalogsApiClient\Model\Bag;

class Catalog extends AbstractSender
{
    public const API_EXCEPTION = CatalogsApiException::class;

    /**
     * Add catalog
     *
     * @param int $storeId
     * @param string $name
     * @return Bag|null
     * @throws CatalogsApiException|DefaultApiException|ValidatorException
     */
    public function addCatalog(int $storeId, string $name): ?Bag
    {
        try {
            $response = $this->sendWithTokenExpiredCatch(
                function () use ($storeId, $name) {
                    return $this->getCatalogsApiInstance($storeId)->addBag(new AddBag(['name' => $name]));
                },
                $storeId
            );

            return $response->getData();
        } catch (CatalogsApiException $e) {
            $this->logApiException($e);
            throw $e;
        }
    }

    /**
     * Find existing catalog by store ID
     *
     * @param int $storeId
     * @param string $name
     * @return Bag|null
     * @throws CatalogsApiException|DefaultApiException|ValidatorException
     */
    public function getCatalog(int $storeId, string $name): ?Bag
    {
        try {
            $getBagsResponse = $this->sendWithTokenExpiredCatch(
                function () use ($storeId, $name) {
                    return $this->getCatalogsApiInstance($storeId)->getBags($name);
                },
                $storeId
            );

            foreach ($getBagsResponse->getData() as $bag) {
                if ($bag->getName() == $name) {
                    return $bag;
                }
            }
        } catch (CatalogsApiException $e) {
            $this->logApiException($e);
            throw $e;
        }

        return null;
    }

    /**
     * Get catalogs api instance
     *
     * @param int $storeId
     * @return BagsApi
     * @return mixed
     * @throws DefaultApiException
     * @throws ValidatorException
     */
    protected function getCatalogsApiInstance(int $storeId): BagsApi
    {
        return $this->getApiInstance('catalogs', $storeId);
    }
}
