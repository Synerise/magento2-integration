<?php

namespace Synerise\Integration\Model\Config\Backend\Synchronization\Enabled;

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
        $url = $this->urlInterface->getUrl('synerise/synchronization');
        return 'When set to <b>Yes</b>, the continuous synchronization based on data changes is automatically started. 
            Full and batch synchronizations can also be scheduled manually. 
            Either from <a href="' . $url . '">Synchronization page</a> or from each model\'s listing actions.';
    }
}
