<?php

namespace Synerise\Integration\Model\Config\Source\EventTracking;

class Events implements \Magento\Framework\Option\ArrayInterface
{
    const OPTIONS = [
        'customer_register_success'                 => 'Customer registration',
        'customer_login'                            => 'Customer login',
        'customer_logout'                           => 'Customer logout',
        'customer_save_after'                       => 'Customer updated',
        'checkout_cart_add_product_complete'        => 'Customer added product to cart',
        'sales_quote_remove_item'                   => 'Customer removed product from cart',
        'sales_quote_save_after'                    => 'Cart updated',
        'checkout_cart_update_items_after'          => 'Cart quantities updated',
        'sales_order_place_after'                   => 'Order updated',
        'catalog_product_save_after'                => 'Product updated',
        'catalog_product_delete_after'              => 'Product deleted',
        'catalog_product_import_bunch_save_after'   => 'Product import',
        'newsletter_subscriber_save_after'          => 'Subscription updated',
        'product_is_salable_change'                 => 'Variable "is salable" changed',
        'stock_status_change'                       => 'Stock status changed'
    ];

    public function toOptionArray()
    {
        $options = [];
        foreach (self::OPTIONS as $value => $label) {
            $options[] = [
                'value' => $value,
                'label' => $label
            ];
        }

        return $options;
    }
}
