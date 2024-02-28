<?php

namespace Synerise\Integration\SyneriseApi\Mapper\Data;

use Synerise\ApiClient\Model\CreateaClientinCRMRequest;

class CustomerMerge
{
    /**
     * Prepare request
     *
     * @param string $email
     * @param string $previousUuid
     * @param string $currentUuid
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
