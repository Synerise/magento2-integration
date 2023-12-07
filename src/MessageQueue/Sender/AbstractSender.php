<?php

namespace Synerise\Integration\MessageQueue\Sender;

use Exception;
use Psr\Log\LoggerInterface;
use Synerise\ApiClient\ApiException;
use Synerise\CatalogsApiClient\ApiException as CatalogApiException;

use Synerise\Integration\SyneriseApi\ConfigFactory;
use Synerise\Integration\SyneriseApi\InstanceFactory;

abstract class AbstractSender
{
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
     * @param ApiException|CatalogApiException $e
     * @return void
     * @throws ApiException|CatalogApiException
     */
    protected function handleApiExceptionAndMaybeUnsetToken(Exception $e, string $mode, int $scopeId)
    {
        if ($this->isTokenExpired($e)) {
            $this->unsetToken($mode, $scopeId);
        } else {
            $this->logger->error(
                'Synerise Api request failed',
                [
                    'exception' => preg_replace('/ response:.*/s', '', $e->getMessage()),
                    'response_body' => preg_replace('/\n/s', '', $e->getResponseBody())
                ]
            );
            throw $e;
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
     * @param $mode
     * @param $scopeId
     * @return void
     */
    protected function unsetToken($mode, $scopeId)
    {
        $this->configFactory->clearConfig($mode, $scopeId);
        $this->apiInstanceFactory->clearInstances($this->configFactory->getScopeKey($mode, $scopeId));
    }
}
