<?php

namespace Synerise\Integration\Search\Container;

use Magento\Framework\Session\SessionManagerInterface;

class SearchResponse
{
    private const CORRELATION_ID_PATTERN = 'search_%s';

    private $currentCorrelationId = null;

    /**
     * @var SessionManagerInterface
     */
    private $session;

    public function __construct(SessionManagerInterface $session)
    {
        $this->session = $session;
    }

    public function setCorrelationId($hash, $correlationId)
    {
        $this->currentCorrelationId = $correlationId;
        $this->session->setData(sprintf(self::CORRELATION_ID_PATTERN, $hash), $correlationId);
    }

    public function getCorrelationId($hash)
    {
        $this->currentCorrelationId = $this->session->getData(sprintf(self::CORRELATION_ID_PATTERN, $hash));
        return $this->currentCorrelationId;
    }

    public function getCurrentCorrelationId()
    {
        return $this->currentCorrelationId;
    }
}