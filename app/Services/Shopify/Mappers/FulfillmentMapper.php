<?php declare(strict_types=1);

namespace App\Services\Shopify\Mappers;

use App\Models\Fulfillment;
use App\Models\Order;
use App\Services\Shopify\Validators\FulfillmentValidator;

class FulfillmentMapper
{
    public static function prepare(Order $order, array $data): Fulfillment
    {
        FulfillmentValidator::validate($data);
        $fulfillment = Fulfillment::updateOrCreate(
            [
                'shopify_id' => $data['id'],
            ],
            [
                'order_id' => $order->id,
                'store_id' => $order->store_id,
                'shopify_id' => $data['id'],
                'title' => $data['name'],
                'status' => $data['displayStatus'],
                'delivered_at' => $data['deliveredAt'],
                'estimated_delivery_at' => $data['estimatedDeliveryAt'],
                'created_at' => $data['createdAt'],
                'updated_at' => $data['updatedAt'],
            ]
        );

        foreach ($data['fulfillmentLineItems']['nodes'] as $node) {
            FulfillmentItemMapper::prepare($node['lineItem'], $fulfillment->id);
        }
        return $fulfillment;
    }
}
