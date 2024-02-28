<?php

namespace Synerise\Integration\SyneriseApi\Mapper\Data;

use Magento\Review\Model\Review;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;

class CustomerFromReview
{
    /**
     * Prepare request
     *
     * @param Review $review
     * @param string $uuid
     * @return CreateaClientinCRMRequest
     */
    public function prepareRequest(Review $review, string $uuid): CreateaClientinCRMRequest
    {
        return new CreateaClientinCRMRequest([
            'uuid' => $uuid,
            'display_name' => $review->getNickname()
        ]);
    }
}
