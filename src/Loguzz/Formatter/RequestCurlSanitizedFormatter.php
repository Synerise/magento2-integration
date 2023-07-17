<?php

namespace Synerise\Integration\Loguzz\Formatter;

use Loguzz\Formatter\RequestCurlFormatter;
use Psr\Http\Message\RequestInterface;

class RequestCurlSanitizedFormatter extends RequestCurlFormatter
{
    protected function parseData(RequestInterface $request, array $options): array
    {
        $data = parent::parseData($request, $options);
        if (isset($data['headers']['Authorization'])) {
            $authorizationString = $data['headers']['Authorization'];
            if ($authorizationString) {
                $data['headers']['Authorization'] = preg_replace('/(Basic |Bearer )(.*)/', '$1{TOKEN}', $authorizationString);
            }
        }

        return $data;
    }
}
