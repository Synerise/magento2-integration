<?php

namespace Synerise\Integration\Model\Config\Backend\Workspaces;

use Magento\Framework\UrlInterface;

class Comment implements \Magento\Config\Model\Config\CommentInterface
{
    protected $urlInterface;

    public function __construct(
        UrlInterface $urlInterface
    ) {
        $this->urlInterface = $urlInterface;
    }

    public function getCommentText($elementValue)
    {
        $url = $this->urlInterface->getUrl('synerise/workspace');
        return 'Head over to <a href="' . $url . '"target="_blank">Workspaces managemnt</a> to add options.';
    }
}