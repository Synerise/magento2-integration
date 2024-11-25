<?php

namespace Synerise\Integration\Search\DataProvider\Autocomplete;

use Magento\CatalogSearch\Model\Autocomplete\DataProvider;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Search\Model\Autocomplete\DataProviderInterface;
use Magento\Search\Model\Autocomplete\ItemFactory;
use Magento\Store\Model\ScopeInterface;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Search\Container\Autocomplete;

class Term implements DataProviderInterface
{
    /**
     * @var Autocomplete
     */
    protected $autocomplete;

    /**
     * @var ItemFactory
     */
    protected $itemFactory;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Logger
     */
    protected $logger;

    public function __construct(
        Autocomplete $autocomplete,
        ItemFactory $itemFactory,
        ScopeConfigInterface $scopeConfig,
        Logger $logger
    ) {
        $this->autocomplete = $autocomplete;
        $this->itemFactory = $itemFactory;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function getItems()
    {
        $result = [];
        try {
            if ($response = $this->autocomplete->search()) {
                $suggestions = $response->getExtras() ? $response->getExtras()->getSuggestions() : [];
                if ($suggestions) {
                    foreach ($suggestions as $suggestion) {
                        $result[] = $this->itemFactory->create([
                            'type' => 'term',
                            'title' => $suggestion->getText()
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->debug($e);
        }

        $limit = $this->getLimit();
        return ($limit) ? array_splice($result, 0, $limit) : $result;
    }

    /**
     * Get autocomplete limit
     *
     * @param int|null $storeId
     * @return int|null
     */
    protected function getLimit(?int $storeId = null): ?int
    {
        return (int) $this->scopeConfig->getValue(
            DataProvider::CONFIG_AUTOCOMPLETE_LIMIT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}