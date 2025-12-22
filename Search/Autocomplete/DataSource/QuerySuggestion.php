<?php

namespace Synerise\Integration\Search\Autocomplete\DataSource;

use Magento\Search\Model\Autocomplete\ItemFactory;
use Magento\Search\Model\QueryFactory;
use Magento\Store\Model\StoreManagerInterface;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Helper\Tracking\Cookie;
use Synerise\Integration\SyneriseApi\Mapper\Search\Autocomplete as Mapper;
use Synerise\Integration\SyneriseApi\Sender\Search as Sender;

class QuerySuggestion implements DataSourceInterface
{
    /**
     * @var DataFactory
     */
    protected $dataFactory;

    /**
     * @var QueryFactory
     */
    protected $queryFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ItemFactory
     */
    protected $itemFactory;

    /**
     * @var Cookie
     */
    protected $cookieHelper;

    /**
     * @var Sender
     */
    protected $sender;

    /**
     * @var Mapper
     */
    protected $mapper;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var array
     */
    protected $sourceConfig;

    public function __construct(
        DataFactory $dataFactory,
        QueryFactory $queryFactory,
        StoreManagerInterface $storeManager,
        Cookie $cookieHelper,
        Sender $sender,
        Mapper $mapper,
        array $sourceConfig
    ) {
        $this->dataFactory = $dataFactory;
        $this->queryFactory = $queryFactory;
        $this->storeManager = $storeManager;
        $this->cookieHelper = $cookieHelper;
        $this->sender = $sender;
        $this->mapper = $mapper;
        $this->sourceConfig = $sourceConfig;
    }

    public function get(): ?DataInterface
    {
        $storeId = $this->storeManager->getStore()->getId();

        $indexId = $this->sourceConfig['index_id'];
        if (!$indexId) {
            throw new \InvalidArgumentException(sprintf('Suggestions index not set for store: %d.', $storeId));
        }

        $request = $this->mapper->prepareRequest(
            $this->queryFactory->get()->getQueryText(),
            $this->sourceConfig['limit'],
            $this->cookieHelper->getSnrsUuid()
        );

        $response = $this->sender->searchAutocomplete(
            $storeId,
            $indexId,
            $request
        );

        $values = [];
        $suggestions = $response->getData() ?: [];
        foreach ($suggestions as $suggestion) {
            if (isset($suggestion['suggestion'])) {
                $values[] = $suggestion['suggestion'];
            }
        }

        return $this->dataFactory->create([
            'values' => $values
        ]);
    }
}