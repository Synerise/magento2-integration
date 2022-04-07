<?php

namespace Synerise\Integration\Model\Config\Backend\Cron;

class Queue extends \Magento\Framework\App\Config\Value
{
    const CRON_STRING_PATH = 'crontab/default/jobs/synerise_sync_queue/schedule/cron_expr';

    const CRON_MODEL_PATH = 'crontab/default/jobs/synerise_sync_queue/run/model';

    const XML_PATH_CRON_QUEUE_ENABLED = 'groups/cron_queue/fields/enabled/value';

    const XML_PATH_CRON_QUEUE_EXPR = 'groups/cron_queue/fields/expr/value';

    /**
     * @var \Magento\Framework\App\Config\ValueFactory
     */

    protected $_configValueFactory;

    /**
     * @var mixed|string
     */

    protected $_runModelPath = '';

    /**
     * CronConfig1 constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\App\Config\ValueFactory $configValueFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param string $runModelPath
     * @param array $data
     */

    public function __construct(
        \Magento\Framework\Model\Context                        $context,
        \Magento\Framework\Registry                             $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface      $config,
        \Magento\Framework\App\Cache\TypeListInterface          $cacheTypeList,
        \Magento\Framework\App\Config\ValueFactory              $configValueFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb           $resourceCollection = null,
        $runModelPath = '',
        array                                                   $data = []
    ) {
        $this->_runModelPath = $runModelPath;
        $this->_configValueFactory = $configValueFactory;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * @return Queue
     * @throws \Exception
     */
    public function afterSave()
    {
        $enabled = $this->getData(self::XML_PATH_CRON_QUEUE_ENABLED);
        $cronExprString = $enabled ? $this->getData(self::XML_PATH_CRON_QUEUE_EXPR) : '';

        try {
            $this->_configValueFactory->create()->load(
                self::CRON_STRING_PATH,
                'path'
            )->setValue(
                $cronExprString
            )->setPath(
                self::CRON_STRING_PATH
            )->save();

            $this->_configValueFactory->create()->load(
                self::CRON_MODEL_PATH,
                'path'
            )->setValue(
                $this->_runModelPath
            )->setPath(
                self::CRON_MODEL_PATH
            )->save();
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__('We can\'t save the Cron expression.'));
        }
        return parent::afterSave();
    }
}
