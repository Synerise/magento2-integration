<?php
namespace Synerise\Integration\MessageQueue\Config\Consumer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\MessageQueue\Consumer\Config\CompositeReader as ConsumerConfigCompositeReader;
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
     * Read values from queue config and make them available via consumer config.
     *
     * @param ConsumerConfigCompositeReader $subject
     * @param array $result
     * @param string|null $scope
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterRead(ConsumerConfigCompositeReader $subject, $result, $scope = null)
    {
        if (isset($result['synerise.queue.events']) && isset($result['synerise.queue.events']['connection'])) {
            $result['synerise.queue.events']['connection'] = $this->getConnectionFromConfig();
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
