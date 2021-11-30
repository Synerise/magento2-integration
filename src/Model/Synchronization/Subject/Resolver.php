<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Synerise\Integration\Model\Synchronization\Subject;

class Resolver
{
    const SYNCHRONIZATION_SUBJECT_CUSTOMER = 'customer';
    const SYNCHRONIZATION_SUBJECT_ORDER = 'order';
    const SYNCHRONIZATION_SUBJECT_PRODUCT = 'product';
    const SYNCHRONIZATION_SUBJECT_SUBSCRIBER = 'subscriber';

    /**
     * Synchronization subject models list
     *
     * @var array
     */
    protected $subjectsPool;

    /**
     * Filter factory
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param array $subjectsPool
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        array $subjectsPool
    ) {
        $this->objectManager = $objectManager;
        $this->subjectsPool = $subjectsPool;
    }

    /**
     * Create Synchronization Subject by model name
     *
     * @param string $model
     * @return AbstractSubject
     */
    public function create(string $model)
    {
        if (!isset($this->subjectsPool[$model])) {
            throw new \InvalidArgumentException($model . ' does not belong to any registered subject');
        }
        return $this->objectManager
            ->create($this->subjectsPool[$model]);
    }
}
