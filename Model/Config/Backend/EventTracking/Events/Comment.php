<?php

namespace Synerise\Integration\Model\Config\Backend\EventTracking\Events;

use Magento\Config\Model\Config\CommentInterface;
use Magento\Framework\UrlInterface;

class Comment implements CommentInterface
{
    /**
     * @var UrlInterface
     */
    protected $urlInterface;

    /**
     * @param UrlInterface $urlInterface
     */
    public function __construct(
        UrlInterface $urlInterface
    ) {
        $this->urlInterface = $urlInterface;
    }

    /**
     * Get comment text
     *
     * @param string $elementValue
     * @return string
     */
    public function getCommentText($elementValue): string
    {
        $url = $this->urlInterface->getUrl('adminhtml/system_config/edit/section/synerise_data');
        return 'Orders are sent via <a href="' . $url .
            '">synchronization</a>. Please make sure it is configured & enabled to collect order events.';
    }
}
