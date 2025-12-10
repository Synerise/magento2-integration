<?php

namespace Synerise\Integration\Search\DataProvider\Autocomplete;

use Magento\Search\Model\Autocomplete\DataProviderInterface;
use Magento\Search\Model\Autocomplete\ItemFactory;
use Magento\Search\Model\QueryFactory;
use Magento\Store\Model\StoreManagerInterface;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Helper\Tracking\Cookie;
use Synerise\Integration\Search\ConfigFactory;
use Synerise\Integration\SyneriseApi\Mapper\Search\Autocomplete as Mapper;
use Synerise\Integration\SyneriseApi\Sender\Search as Sender;

class Suggestion implements DataProviderInterface
{
    /**
     * @var QueryFactory
     */
    protected $queryFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ConfigFactory
     */
    protected $configFactory;

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

    public function __construct(
        QueryFactory $queryFactory,
        StoreManagerInterface $storeManager,
        ConfigFactory $configFactory,
        ItemFactory $itemFactory,
        Cookie $cookieHelper,
        Sender $sender,
        Mapper $mapper,
        Logger $logger
    ) {
        $this->queryFactory = $queryFactory;
        $this->storeManager = $storeManager;
        $this->configFactory = $configFactory;
        $this->itemFactory = $itemFactory;
        $this->cookieHelper = $cookieHelper;
        $this->sender = $sender;
        $this->mapper = $mapper;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function getItems()
    {
        $result = [];

        try {
            $storeId = $this->storeManager->getStore()->getId();
            $config = $this->configFactory->create($storeId);

            if (!$config->isSuggestionsAutocompleteEnabled()) {
                return $result;
            }

            $indexId = $config->getSuggestionsIndex();
            if (!$indexId) {
                throw new \InvalidArgumentException(sprintf('Suggestions index not set for store: %d.', $storeId));
            }

            $request = $this->mapper->prepareRequest(
                $this->queryFactory->get()->getQueryText(),
                $config->getSuggestionsAutocompleteLimit(),
                $this->cookieHelper->getSnrsUuid()
            );

            $response = $this->sender->searchAutocomplete(
                $storeId,
                $indexId,
                $request
            );

            if ($response) {
                $suggestions = $response->getData() ?: [];
                foreach ($suggestions as $suggestion) {
                    if (isset($suggestion['suggestion'])) {
                        $result[] = $this->itemFactory->create([
                            'type' => 'term',
                            'title' => $suggestion['suggestion']
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->debug($e);
        }

        return $result;
    }
}