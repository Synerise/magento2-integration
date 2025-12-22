<?php

namespace Synerise\Integration\Search\Autocomplete\DataProvider;

use Magento\Search\Model\Autocomplete\DataProviderInterface;
use Magento\Search\Model\Autocomplete\ItemFactory;
use Synerise\Integration\Helper\Logger;
use Synerise\Integration\Search\Autocomplete\DataSource\DataSourceInterface;

class Query implements DataProviderInterface
{
    /**
     * @var DataSourceInterface
     */
    protected $dataSource;

    /**
     * @var ItemFactory
     */
    protected $itemFactory;

    /**
     * @var Logger
     */
    protected $logger;

    public function __construct(
        DataSourceInterface $dataSource,
        ItemFactory $itemFactory,
        Logger $logger
    ) {
        $this->dataSource = $dataSource;
        $this->itemFactory = $itemFactory;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function getItems()
    {
        $result = [];

        try {
            $dataSource = $this->dataSource->get();
            if (!$dataSource || empty($dataSource->getValues())) {
                return [];
            }

            if (!empty($dataSource->getHeader())) {
                $result[] = $this->itemFactory->create([
                    'type' => 'header',
                    'title' => __($dataSource->getHeader())
                ]);
            }

            foreach ($dataSource->getValues() as $value) {
                $result[] = $this->itemFactory->create([
                    'type' => 'term',
                    'title' => $value
                ]);
            }

        } catch (\Exception $e) {
            $this->logger->debug($e);
        }

        return $result;
    }
}