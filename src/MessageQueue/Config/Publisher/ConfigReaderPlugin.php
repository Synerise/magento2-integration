<?php

namespace Synerise\Integration\MessageQueue\Config\Publisher;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\MessageQueue\Publisher\Config\CompositeReader as PublisherConfigCompositeReader;
use Synerise\Integration\Model\Config\Source\MessageQueue\Connection;


class ConfigReaderPlugin
{
    /**
     * @var ScopeConfigInterface
     */
    private $config;

    /**
     * @param ScopeConfigInterface $config
     */
    public function __construct(ScopeConfigInterface $config)
    {
        $this->config = $config;
    }
    /**
     * Read values from queue config and make them available via publisher config
     *
     * @param PublisherConfigCompositeReader $subject
     * @param array $result
     * @param string|null $scope
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterRead(PublisherConfigCompositeReader $subject, $result, $scope = null)
    {
        if (isset($result['synerise.queue.events']) && isset($result['synerise.queue.events']['connection'])) {
            $result['synerise.queue.events']['connection']['name'] = $this->getConnectionFromConfig();
        }

        if (isset($result['synerise.queue.updates']) && isset($result['synerise.queue.updates']['connection'])) {
            $result['synerise.queue.updates']['connection']['name'] = $this->getConnectionFromConfig();
        }

        return $result;
    }

    protected function getConnectionFromConfig()
    {
        return $this->config->getValue(
            Connection::CONFIG_PATH,
        ) ?: 'db';
    }
}
