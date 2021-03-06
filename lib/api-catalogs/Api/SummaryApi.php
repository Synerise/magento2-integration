<?php
/**
 * SummaryApi
 * PHP version 7.2
 *
 * @category Class
 * @package  Synerise\CatalogsApiClient
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 */

/**
 * Items-collector API Reference
 *
 * This is API for Catalogs
 *
 * The version of the OpenAPI document: 1.0.0
 * Contact: marcin.stelmach@synerise.com
 * Generated by: https://openapi-generator.tech
 * OpenAPI Generator version: 5.1.0-SNAPSHOT
 */

/**
 * NOTE: This class is auto generated by OpenAPI Generator (https://openapi-generator.tech).
 * https://openapi-generator.tech
 * Do not edit the class manually.
 */

namespace Synerise\CatalogsApiClient\Api;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;
use Synerise\CatalogsApiClient\ApiException;
use Synerise\CatalogsApiClient\Configuration;
use Synerise\CatalogsApiClient\HeaderSelector;
use Synerise\CatalogsApiClient\ObjectSerializer;

/**
 * SummaryApi Class Doc Comment
 *
 * @category Class
 * @package  Synerise\CatalogsApiClient
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 */
class SummaryApi
{
    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * @var Configuration
     */
    protected $config;

    /**
     * @var HeaderSelector
     */
    protected $headerSelector;

    /**
     * @var int Host index
     */
    protected $hostIndex;

    /**
     * @param ClientInterface $client
     * @param Configuration   $config
     * @param HeaderSelector  $selector
     * @param int             $hostIndex (Optional) host index to select the list of hosts if defined in the OpenAPI spec
     */
    public function __construct(
        ClientInterface $client = null,
        Configuration $config = null,
        HeaderSelector $selector = null,
        $hostIndex = 0
    ) {
        $this->client = $client ?: new Client();
        $this->config = $config ?: new Configuration();
        $this->headerSelector = $selector ?: new HeaderSelector();
        $this->hostIndex = $hostIndex;
    }

    /**
     * Set the host index
     *
     * @param int $hostIndex Host index (required)
     */
    public function setHostIndex($hostIndex)
    {
        $this->hostIndex = $hostIndex;
    }

    /**
     * Get the host index
     *
     * @return int Host index
     */
    public function getHostIndex()
    {
        return $this->hostIndex;
    }

    /**
     * @return Configuration
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Operation getSummary
     *
     * Get summary for catalogs per bp
     *
     * @param  int $bp_id Business Profile Id (required)
     *
     * @throws \Synerise\CatalogsApiClient\ApiException on non-2xx response
     * @throws \InvalidArgumentException
     * @return \Synerise\CatalogsApiClient\Model\CatalogSummary
     */
    public function getSummary($bp_id)
    {
        list($response) = $this->getSummaryWithHttpInfo($bp_id);
        return $response;
    }

    /**
     * Operation getSummaryWithHttpInfo
     *
     * Get summary for catalogs per bp
     *
     * @param  int $bp_id Business Profile Id (required)
     *
     * @throws \Synerise\CatalogsApiClient\ApiException on non-2xx response
     * @throws \InvalidArgumentException
     * @return array of \Synerise\CatalogsApiClient\Model\CatalogSummary, HTTP status code, HTTP response headers (array of strings)
     */
    public function getSummaryWithHttpInfo($bp_id)
    {
        $request = $this->getSummaryRequest($bp_id);

        try {
            $options = $this->createHttpClientOption();
            try {
                $response = $this->client->send($request, $options);
            } catch (RequestException $e) {
                throw new ApiException(
                    "[{$e->getCode()}] {$e->getMessage()}",
                    $e->getCode(),
                    $e->getResponse() ? $e->getResponse()->getHeaders() : null,
                    $e->getResponse() ? (string) $e->getResponse()->getBody() : null
                );
            }

            $statusCode = $response->getStatusCode();

            if ($statusCode < 200 || $statusCode > 299) {
                throw new ApiException(
                    sprintf(
                        '[%d] Error connecting to the API (%s)',
                        $statusCode,
                        $request->getUri()
                    ),
                    $statusCode,
                    $response->getHeaders(),
                    $response->getBody()
                );
            }

            $responseBody = $response->getBody();
            switch($statusCode) {
                case 200:
                    if ('\Synerise\CatalogsApiClient\Model\CatalogSummary' === '\SplFileObject') {
                        $content = $responseBody; //stream goes to serializer
                    } else {
                        $content = (string) $responseBody;
                    }

                    return [
                        ObjectSerializer::deserialize($content, '\Synerise\CatalogsApiClient\Model\CatalogSummary', []),
                        $response->getStatusCode(),
                        $response->getHeaders()
                    ];
            }

            $returnType = '\Synerise\CatalogsApiClient\Model\CatalogSummary';
            $responseBody = $response->getBody();
            if ($returnType === '\SplFileObject') {
                $content = $responseBody; //stream goes to serializer
            } else {
                $content = (string) $responseBody;
            }

            return [
                ObjectSerializer::deserialize($content, $returnType, []),
                $response->getStatusCode(),
                $response->getHeaders()
            ];

        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\Synerise\CatalogsApiClient\Model\CatalogSummary',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    break;
            }
            throw $e;
        }
    }

    /**
     * Operation getSummaryAsync
     *
     * Get summary for catalogs per bp
     *
     * @param  int $bp_id Business Profile Id (required)
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function getSummaryAsync($bp_id)
    {
        return $this->getSummaryAsyncWithHttpInfo($bp_id)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation getSummaryAsyncWithHttpInfo
     *
     * Get summary for catalogs per bp
     *
     * @param  int $bp_id Business Profile Id (required)
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function getSummaryAsyncWithHttpInfo($bp_id)
    {
        $returnType = '\Synerise\CatalogsApiClient\Model\CatalogSummary';
        $request = $this->getSummaryRequest($bp_id);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    $responseBody = $response->getBody();
                    if ($returnType === '\SplFileObject') {
                        $content = $responseBody; //stream goes to serializer
                    } else {
                        $content = (string) $responseBody;
                    }

                    return [
                        ObjectSerializer::deserialize($content, $returnType, []),
                        $response->getStatusCode(),
                        $response->getHeaders()
                    ];
                },
                function ($exception) {
                    $response = $exception->getResponse();
                    $statusCode = $response->getStatusCode();
                    throw new ApiException(
                        sprintf(
                            '[%d] Error connecting to the API (%s)',
                            $statusCode,
                            $exception->getRequest()->getUri()
                        ),
                        $statusCode,
                        $response->getHeaders(),
                        $response->getBody()
                    );
                }
            );
    }

    /**
     * Create request for operation 'getSummary'
     *
     * @param  int $bp_id Business Profile Id (required)
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function getSummaryRequest($bp_id)
    {
        // verify the required parameter 'bp_id' is set
        if ($bp_id === null || (is_array($bp_id) && count($bp_id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $bp_id when calling getSummary'
            );
        }

        $resourcePath = '/internal/bags/summary';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        if ($bp_id !== null) {
            if('form' === 'form' && is_array($bp_id)) {
                foreach($bp_id as $key => $value) {
                    $queryParams[$key] = $value;
                }
            }
            else {
                $queryParams['bpId'] = $bp_id;
            }
        }




        if ($multipart) {
            $headers = $this->headerSelector->selectHeadersForMultipart(
                ['application/json']
            );
        } else {
            $headers = $this->headerSelector->selectHeaders(
                ['application/json'],
                []
            );
        }

        // for model (json/xml)
        if (count($formParams) > 0) {
            if ($multipart) {
                $multipartContents = [];
                foreach ($formParams as $formParamName => $formParamValue) {
                    $formParamValueItems = is_array($formParamValue) ? $formParamValue : [$formParamValue];
                    foreach ($formParamValueItems as $formParamValueItem) {
                        $multipartContents[] = [
                            'name' => $formParamName,
                            'contents' => $formParamValueItem
                        ];
                    }
                }
                // for HTTP post (form)
                $httpBody = new MultipartStream($multipartContents);

            } elseif ($headers['Content-Type'] === 'application/json') {
                $httpBody = \GuzzleHttp\json_encode($formParams);

            } else {
                // for HTTP post (form)
                $httpBody = \GuzzleHttp\Psr7\Query::build($formParams);
            }
        }


        $defaultHeaders = [];
        if ($this->config->getUserAgent()) {
            $defaultHeaders['User-Agent'] = $this->config->getUserAgent();
        }

        $headers = array_merge(
            $defaultHeaders,
            $headerParams,
            $headers
        );

        $query = \GuzzleHttp\Psr7\Query::build($queryParams);
        return new Request(
            'GET',
            $this->config->getHost() . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Create http client option
     *
     * @throws \RuntimeException on file opening failure
     * @return array of http client options
     */
    protected function createHttpClientOption()
    {
        $options = [];
        if ($this->config->getDebug()) {
            $options[RequestOptions::DEBUG] = fopen($this->config->getDebugFile(), 'a');
            if (!$options[RequestOptions::DEBUG]) {
                throw new \RuntimeException('Failed to open the debug file: ' . $this->config->getDebugFile());
            }
        }

        return $options;
    }
}
