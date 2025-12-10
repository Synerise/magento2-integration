<?php

namespace Synerise\Integration\Search\DataProvider\Autocomplete\Product;

use Magento\Search\Model\Autocomplete\ItemInterface;

class Item extends \Magento\Framework\DataObject implements ItemInterface
{
    public function getTitle()
    {
        return $this->_getData('title');
    }

    public function getType()
    {
        return $this->_getData('type');
    }

    public function getImage()
    {
        return $this->_getData('image');
    }

    public function getUrl()
    {
        return $this->_getData('url');
    }

    public function getPrice()
    {
        return $this->_getData('price');
    }

    public function getClickData()
    {
        return $this->_getData('clickData');
    }
}