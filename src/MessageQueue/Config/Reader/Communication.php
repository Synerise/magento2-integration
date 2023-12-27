<?php
namespace Synerise\Integration\MessageQueue\Config\Reader;

use Magento\Framework\Communication\ConfigInterface;
use Synerise\Integration\Communication\Config;

class Communication implements \Magento\Framework\Config\ReaderInterface
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
     * @param $scope
     * @return array
     */
    public function read($scope = null)
    {
        return  [
            ConfigInterface::TOPICS => $this->config->getTopics()
        ];
    }
}
