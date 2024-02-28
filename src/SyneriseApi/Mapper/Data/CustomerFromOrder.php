<?php

namespace Synerise\Integration\SyneriseApi\Mapper\Data;

use Magento\Sales\Model\Order;
use Synerise\ApiClient\Model\CreateaClientinCRMRequest;

class CustomerFromOrder
{
    /**
     * Prepare guest customer request from order
     *
     * @param Order $order
     * @param string|null $uuid
     * @return CreateaClientinCRMRequest|null
     */
    public function prepareRequest(Order $order, ?string $uuid): CreateaClientinCRMRequest
    {
        $shippingAddress = $order->getShippingAddress();

        $phone = null;
        if ($shippingAddress) {
            $phone = $shippingAddress->getTelephone();
        }

        return new CreateaClientinCRMRequest([
            'email' => $order->getCustomerEmail(),
            'uuid' => $uuid,
            'phone' => $phone,
            'first_name' => $order->getCustomerFirstname(),
            'last_name' => $order->getCustomerLastname()
        ]);
    }
}
