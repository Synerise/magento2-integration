<?php

namespace Synerise\Integration\Helper\Api\Factory;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Loguzz\Middleware\LogMiddleware;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Loguzz\Formatter\RequestCurlSanitizedFormatter;
use Synerise\Integration\Model\ApiConfig;

abstract class AbstractApiFactory
{

    /**
     * @var mixed
     */
    protected $instances;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param ApiConfig $apiConfig
     * @return mixed
     */
    abstract public function create(ApiConfig $apiConfig);

    /**
     * @param ApiConfig $apiConfig
     * @return mixed
     */
    abstract public function get(ApiConfig $apiConfig);

    /**
     * @param ApiConfig $apiConfig
     * @return Client
     */
    protected function getGuzzleClient(ApiConfig $apiConfig): Client
    {
        $options = [
            'connect_timeout' => $apiConfig->getTimeout(),
            'timeout' => $apiConfig->getTimeout()
        ];

        if ($apiConfig->isLoggerEnabled()) {
            $LogMiddleware = new LogMiddleware(
                $this->logger,
                ['request_formatter' => new RequestCurlSanitizedFormatter()]
            );

            $handlerStack = HandlerStack::create();
            $handlerStack->push($LogMiddleware, 'logger');
            $options = [
                'handler' => $handlerStack
            ];
        }

        return new Client($options);
    }
}