<?php

namespace Synerise\Integration\SyneriseApi\Mapper;

use Synerise\ApiClient\Model\CreateaClientinCRMRequest;

class CustomerMerge
{
    /**
     * @return CreateaClientinCRMRequest[]
     */
    public function prepareRequest(string $email, string $previousUuid, string $currentUuid): array
    {
        return [
            new CreateaClientinCRMRequest([
                'email' => $email,
                'uuid' => $currentUuid
            ]),
            new CreateaClientinCRMRequest([
                'email' => $email,
                'uuid' => $previousUuid
            ])
        ];
    }
}