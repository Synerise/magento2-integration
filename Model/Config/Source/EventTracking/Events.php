<?php

namespace Synerise\Integration\Model\Config\Source\EventTracking;

class Events implements \Magento\Framework\Data\OptionSourceInterface
{
    public const OPTIONS = [
        'customer_register_success'                 => 'Customer registration',
        'customer_login'                            => 'Customer login',
        'customer_logout'                           => 'Customer logout',
        'customer_save_after'                       => 'Customer updated',
        'customer_delete'                           => 'Customer deleted',
        'customer_import_interceptor'               => 'Customer import',
        'checkout_cart_add_product_complete'        => 'Customer added product to cart',
        'sales_quote_remove_item'                   => 'Customer removed product from cart',
        'product_review_save_after'                 => 'Customer reviewed product',
        'sales_quote_save_after'                    => 'Cart updated',
        'checkout_cart_update_items_after'          => 'Cart quantities updated',
        'wishlist_add_product'                      => 'Customer added product to favourites',
        'wishlist_item_delete_after'                => 'Customer removed product from favourites',
        'sales_order_place_after'                   => 'Order updated',
        'catalog_product_save_after'                => 'Product updated',
        'catalog_product_delete_after'              => 'Product deleted',
        'catalog_product_import_bunch_save_after'   => 'Product import',
        'product_is_salable_change'                 => 'Product "is salable" value changed',
        'stock_status_change'                       => 'Product stock status changed',
        'newsletter_subscriber_save_after'          => 'Subscription updated'
    ];

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
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
