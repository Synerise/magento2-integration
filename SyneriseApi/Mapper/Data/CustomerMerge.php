<?php

namespace Synerise\Integration\SyneriseApi\Mapper\Data;

use Synerise\ApiClient\Model\CreateaClientinCRMRequest;

class CustomerMerge
{
    /**
     * Prepare request
     *
     * @param string $email
     * @param string $currentUuid
     * @param string|null $previousUuid
     * @return CreateaClientinCRMRequest[]
     */
    public function prepareRequest(string $email, string $currentUuid, ?string $previousUuid = null): array
    {
        $response = [
            new CreateaClientinCRMRequest([
                'email' => $email,
                'uuid' => $currentUuid
            ])
        ];

        if ($previousUuid) {
            $response[] = new CreateaClientinCRMRequest([
                'email' => $email,
                'uuid' => $previousUuid
            ]);
        }

        return $response;
    }
}
