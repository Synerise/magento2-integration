<?php

namespace Synerise\Integration\SyneriseApi\Sender;

use Exception;
use Magento\Framework\Exception\ValidatorException;
use Synerise\ApiClient\ApiException;
use Synerise\CatalogsApiClient\ApiException as CatalogApiException;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Model\Workspace\Config as WorkspaceConfig;
use Synerise\Integration\Model\Workspace\ConfigFactory as WorkspaceConfigFactory;
use Synerise\Integration\SyneriseApi\Config as ApiConfig;
use Synerise\Integration\SyneriseApi\ConfigFactory as ApiConfigFactory;
use Synerise\Integration\SyneriseApi\InstanceFactory;

abstract class AbstractSender
{
    public const API_EXCEPTION = ApiException::class;

    /**
     * @var Logger
     */
    protected $loggerHelper;

    /**
     * @var ApiConfigFactory
     */
    protected $apiConfigFactory;

    /**
     * @var InstanceFactory
     */
    protected $apiInstanceFactory;

    /**
     * @var WorkspaceConfigFactory
     */
    protected $workspaceConfigFactory;

    /**
     * @var ApiConfig[]
     */
    protected $config;

    /**
     * @var array
     */
    protected $instances;

    /**
     * @param Logger $logger
     * @param ApiConfigFactory $apiConfigFactory
     * @param InstanceFactory $apiInstanceFactory
     * @param WorkspaceConfigFactory $workspaceConfigFactory
     */
    public function __construct(
        Logger $logger,
        ApiConfigFactory $apiConfigFactory,
        InstanceFactory $apiInstanceFactory,
        WorkspaceConfigFactory $workspaceConfigFactory
    ) {
        $this->loggerHelper = $logger;
        $this->apiConfigFactory = $apiConfigFactory;
        $this->apiInstanceFactory = $apiInstanceFactory;
        $this->workspaceConfigFactory = $workspaceConfigFactory;
    }

    /**
     * Send request with a token expired catch
     *
     * @param callable $send
     * @param int $storeId
     * @return mixed
     * @throws \Exception
     */
    protected function sendWithTokenExpiredCatch(callable $send, int $storeId)
    {
        try {
            return $send();
        } catch (\Exception $e) {
            $apiExceptionClass = self::API_EXCEPTION;
            if ($e instanceof $apiExceptionClass && $this->isTokenExpired($e)) {
                $this->clearCachedInstances($storeId);
                return $send();
            } else {
                throw $e;
            }
        }
    }

    /**
     * Check if token is expired
     *
     * @param ApiException|CatalogApiException $e
     * @return bool
     */
    protected function isTokenExpired(Exception $e): bool
    {
        return ($e->getCode() == 401 && $e->getResponseObject()->getError() == 'Token expired.');
    }

    /**
     * Log API exception
     *
     * @param ApiException|CatalogApiException $e
     * @return void
     */
    protected function logApiException(Exception $e)
    {
        $this->loggerHelper->getLogger()->error(
            'Synerise Api request failed',
            [
                'exception' => preg_replace('/ response:.*/s', '', $e->getMessage()),
                'response_body' => preg_replace('/\n/s', '', (string) $e->getResponseBody())
            ]
        );
    }

    /**
     * Clear cached instance
     *
     * @param int $storeId
     * @return void
     */
    protected function clearCachedInstances(int $storeId)
    {
        $this->instances[$storeId] = [];
    }

    /**
     * Get API instance
     *
     * @param string $type
     * @param int $storeId
     * @return mixed
     * @throws ApiException
     * @throws ValidatorException
     */
    public function getApiInstance(string $type, int $storeId)
    {
        if (!isset($this->instances[$storeId][$type])) {
            $this->instances[$storeId][$type] = $this->apiInstanceFactory->createApiInstance(
                $type,
                $this->getApiConfig($storeId),
                $this->getWorkspaceConfig($storeId)
            );
        }
        return $this->instances[$storeId][$type];
    }

    /**
     * Get Api config
     *
     * @param int $storeId
     * @return ApiConfig
     * @throws ApiException
     * @throws ValidatorException
     */
    public function getApiConfig(int $storeId): ApiConfig
    {
        if (!isset($this->config['api'][$storeId])) {
            $this->config['api'][$storeId] = $this->apiConfigFactory->create($storeId);
        }
        return $this->config['api'][$storeId];
    }

    /**
     * Get workspace config
     *
     * @param int $storeId
     * @return WorkspaceConfig
     */
    public function getWorkspaceConfig(int $storeId): WorkspaceConfig
    {
        if (!isset($this->config['workspace'][$storeId])) {
            $this->config['workspace'][$storeId] = $this->workspaceConfigFactory->create($storeId);
        }
        return $this->config['workspace'][$storeId];
    }
}
