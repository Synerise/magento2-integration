<?php

namespace Synerise\Integration\Block\Search;

use Magento\Framework\View\Element\Template;
use Magento\Search\Model\QueryFactory;

class NoResults extends Template
{
    /**
     * @var QueryFactory
     */
    private $queryFactory;

    public function __construct(
        QueryFactory $queryFactory,
        Template\Context $context, array
        $data = []
    )
    {
        $this->queryFactory = $queryFactory;

        parent::__construct($context, $data);
    }

    public function getResultCount()
    {
        return $this->_getQuery()->getNumResults();
    }

    protected function _getQuery()
    {
        return $this->queryFactory->get();
    }
}