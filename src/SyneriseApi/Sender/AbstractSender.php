<?php

namespace Synerise\Integration\SyneriseApi\Sender;

use Exception;
use Magento\Framework\Exception\ValidatorException;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\ApiException;
use Synerise\CatalogsApiClient\ApiException as CatalogApiException;
use Synerise\Integration\SyneriseApi\Config;
use Synerise\Integration\SyneriseApi\ConfigFactory;
use Synerise\Integration\SyneriseApi\InstanceFactory;

abstract class AbstractSender
{
    const API_EXCEPTION = ApiException::class;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ConfigFactory
     */
    protected $configFactory;

    /**
     * @var InstanceFactory
     */
    protected $apiInstanceFactory;

    /**
     * @var Config[]
     */
    protected $config;

    /**
     * @var array
     */
    protected $instances;

    public function __construct(
        LoggerInterface $logger,
        ConfigFactory $configFactory,
        InstanceFactory $apiInstanceFactory
    ) {
        $this->logger = $logger;
        $this->apiInstanceFactory = $apiInstanceFactory;
        $this->configFactory = $configFactory;
    }

    /**
     * @param callable $send
     * @param int $storeId
     * @return mixed
     * @throws \Exception
     */
    protected function sendWithTokenExpiredCatch(callable $send, int $storeId) {
        try {
            return $send();
        } catch (\Exception $e) {
            $apiExceptionClass = self::API_EXCEPTION;
            if($e instanceof $apiExceptionClass && $this->isTokenExpired($e)) {
                $this->clearCachedInstances($storeId);
                return $send();
            } else {
                throw $e;
            }
        }
    }

    /**
     * @param ApiException|CatalogApiException $e
     * @return bool
     */
    protected function isTokenExpired(Exception $e): bool
    {
        return ($e->getCode() == 401 && $e->getResponseObject()->getError() == 'Token expired.');
    }

    /**
     * @param ApiException|CatalogApiException $e
     * @return void
     */
    protected function logApiException(Exception $e) {
        $this->logger->error(
            'Synerise Api request failed',
            [
                'exception' => preg_replace('/ response:.*/s', '', $e->getMessage()),
                'response_body' => preg_replace('/\n/s', '', (string) $e->getResponseBody())
            ]
        );
    }

    /**
     * @param int $storeId
     * @return void
     */
    protected function clearCachedInstances(int $storeId)
    {
        $this->config[$storeId] = [];
        $this->instances[$storeId] = [];
    }

    /**
     * @param string $type
     * @param int $storeId
     * @return mixed
     * @throws ApiException
     * @throws ValidatorException
     */
    public function getApiInstance(string $type, int $storeId) {
        if (!isset($this->instances[$storeId][$type])) {
            $this->instances[$storeId][$type] = $this->apiInstanceFactory->createApiInstance(
                $type,
                $this->getConfig($storeId)
            );
        }
        return $this->instances[$storeId][$type];
    }

    /**
     * @throws ApiException
     * @throws ValidatorException
     */
    public function getConfig($storeId): Config
    {
        if (!isset($this->config[$storeId])) {
            $this->config[$storeId] = $this->configFactory->createConfig($storeId);
        }
        return $this->config[$storeId];
    }

}
