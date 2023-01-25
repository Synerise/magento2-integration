<?php

namespace Synerise\Integration\Helper\Api;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Loguzz\Middleware\LogMiddleware;
use Psr\Log\LoggerInterface;
use Synerise\Integration\Loguzz\Formatter\RequestCurlSanitizedFormatter;

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
     * @param bool $includeLogger
     * @return Client
     */
    protected function getGuzzleClient(bool $includeLogger = false): Client
    {
        $options = [];
        if ($includeLogger) {
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