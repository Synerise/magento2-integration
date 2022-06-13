<?php

namespace Synerise\Integration\Model\Config\Backend\BusinessProfiles;

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
        $url = $this->urlInterface->getUrl('synerise/businessprofile');
        return 'Head over to <a href="' . $url . '"target="_blank">Business Profiles managemnt</a> to add options.';
    }
}