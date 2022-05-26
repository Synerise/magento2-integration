<?php

namespace Synerise\Integration\Ui\DataProvider\Customer;

use Magento\Framework\Data\Collection;

class AddSyneriseUpdatedAtFieldToCollection implements \Magento\Ui\DataProvider\AddFieldToCollectionInterface
{


    public function addField(Collection $collection, $field, $alias = null)
    {
        $collection->joinField('synerise_updated_at', 'synerise_sync_customer', 'synerise_updated_at', 'customer_id=entity_id', null, 'left');
    }
}
