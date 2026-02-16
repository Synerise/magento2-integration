<?php

namespace Synerise\Integration\Controller\Ajax\Search;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Search\Model\AutocompleteInterface;

class Suggest implements HttpGetActionInterface
{

    /**
     * @var  AutocompleteInterface
     */
    private $autocomplete;

    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @param Context $context
     * @param AutocompleteInterface $autocomplete
     */
    public function __construct(
        Context $context,
        AutocompleteInterface $autocomplete
    ) {
        $this->autocomplete = $autocomplete;
        $this->resultFactory = $context->getResultFactory();
    }

    /**
     * @return ResultInterface
     */
    public function execute()
    {
        $autocompleteData = $this->autocomplete->getItems();
        $responseData = [];
        $key = 0;
        foreach ($autocompleteData as $resultItem) {
            $data = $resultItem->toArray();
            $data['key'] = $key++;
            $responseData[] = $data;
        }
        /** @var Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($responseData);
        return $resultJson;
    }
}