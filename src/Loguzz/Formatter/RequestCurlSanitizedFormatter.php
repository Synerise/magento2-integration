<?php

namespace Synerise\Integration\Loguzz\Formatter;

use Loguzz\Formatter\RequestCurlFormatter;
use Psr\Http\Message\RequestInterface;

class RequestCurlSanitizedFormatter extends RequestCurlFormatter
{
    protected function extractArguments(RequestInterface $request, array $options)
    {
        parent::extractArguments($request, $options);
        if (!isset($this->options['headers']['Authorization'])) {
            return;
        }

        $authorizationString = $this->options['headers']['Authorization'];
        if ($authorizationString) {
            $this->options['headers']['Authorization'] = preg_replace('/(Bearer )(.*)/', '$1{TOKEN}', $authorizationString);
        }
    }
}
