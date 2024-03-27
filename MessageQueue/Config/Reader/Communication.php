<?php
namespace Synerise\Integration\MessageQueue\Config\Reader;

use Magento\Framework\Communication\ConfigInterface;
use Magento\Framework\Config\ReaderInterface;
use Synerise\Integration\Communication\Config;

class Communication implements ReaderInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function read($scope = null)
    {
        return  [
            ConfigInterface::TOPICS => $this->config->getTopics()
        ];
    }
}
