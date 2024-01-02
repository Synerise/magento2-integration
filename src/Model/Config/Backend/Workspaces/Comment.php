<?php

namespace Synerise\Integration\Model\Config\Backend\Workspaces;

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
        $url = $this->urlInterface->getUrl('synerise/workspace');
        return 'Head over to <a href="' . $url . '"target="_blank">Workspaces managemnt</a> to add options.';
    }
}
