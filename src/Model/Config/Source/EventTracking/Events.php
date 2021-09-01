<?php

namespace Synerise\Integration\Model\Config\Source\EventTracking;

class Events implements \Magento\Framework\Option\ArrayInterface
{
    CONST OPTIONS = [
        'customer_register_success'             => 'Customer registration',
        'customer_login'                        => 'Customer login',
        'customer_logout'                       => 'Customer logout',
        'customer_save_after'                   => 'Customer data saved',
        'adminhtml_customer_save_after'         => 'Customer account edited by admin',
        'checkout_cart_add_product_complete'    => 'Customer added product to cart',
        'sales_quote_remove_item'               => 'Customer removed product from cart',
        'sales_order_place_after'               => 'Customer placed order',
        'catalog_product_save_after'            => 'Product updated',
        'catalog_product_delete_after'          => 'Product deleted',
        'newsletter_subscriber_save_after'      => 'Subscription updated'
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