<?php

namespace Synerise\Integration\Ui\DataProvider\Product;

use Magento\Framework\Data\Collection;

class AddSyneriseUpdatedAtFieldToCollection implements \Magento\Ui\DataProvider\AddFieldToCollectionInterface
{

    public function addField(Collection $collection, $field, $alias = null)
    {
        $collection->joinField('synerise_updated_at', 'synerise_sync_product', 'synerise_updated_at', 'product_id=entity_id', null, 'left');
    }
}
