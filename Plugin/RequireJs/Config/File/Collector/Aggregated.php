<?php

namespace Synerise\Integration\Plugin\RequireJs\Config\File\Collector;

use Magento\Framework\RequireJs\Config\File\Collector\Aggregated as Subject;
use Magento\Framework\Search\EngineResolverInterface;

class Aggregated
{
    /**
     * @var EngineResolverInterface
     */
    protected $engineResolver;

    public function __construct(
        EngineResolverInterface $engineResolver
    ) {
        $this->engineResolver = $engineResolver;
    }

    public function afterGetFiles(Subject $subject, $result)
    {
        if ($this->engineResolver->getCurrentSearchEngine() != 'synerise_ai') {
            foreach ($result as $key => $file) {
                // Module to exclude
                if ($file->getModule() == "Synerise_Integration") {
                    unset($result[$key]);
                }
            }
        }
        return $result;
    }
}