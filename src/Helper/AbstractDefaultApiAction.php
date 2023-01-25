<?php

namespace Synerise\Integration\Helper;

use Magento\Framework\Exception\ValidatorException;
use Synerise\ApiClient\Api\DefaultApi;
use Synerise\ApiClient\ApiException;
use Synerise\Integration\Helper\Api\DefaultApiFactory;

abstract class AbstractDefaultApiAction
{
    /**
     * @var Api
     */
    protected $apiHelper;

    /**
     * @var DefaultApiFactory
     */
    protected $defaultApiFactory;
    
    /**
     * @var DefaultApi[]
     */
    protected $defaultApi;

    public function __construct(
        Api $apiHelper,
        DefaultApiFactory $defaultApiFactory
    ) {
        $this->apiHelper = $apiHelper;
        $this->defaultApiFactory = $defaultApiFactory;
    }

    /**
     * @param int|null $storeId
     * @return DefaultApi
     * @throws ValidatorException
     * @throws ApiException
     */
    public function getDefaultApiInstance(?int $storeId = null): DefaultApi
    {
        $key = $storeId != null ? $storeId : 'default';
        if (!isset($this->defautlApi[$key])) {
            $this->defaultApi = $this->defaultApiFactory->create($this->apiHelper->getApiConfigByScope($storeId));
        }

        return $this->defaultApi[$key];
    }
}