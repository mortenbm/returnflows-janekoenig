<?php declare(strict_types=1);

namespace App\Services\Shopify\Mappers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Shopify\Validators\OrderItemValidator;
use Cknow\Money\Money;

class OrderItemMapper
{
    public static function prepare(array $data): OrderItem
    {
        OrderItemValidator::validate($data);

        $order = Order::query()->firstWhere(
            column: 'shopify_id',
            operator: '=',
            value: $data['__parentId']
        );
        if (!$order->id) {
            throw new \Exception('Order doesn\'t exist for item: ' . $data['id']);
        }

        return OrderItem::updateOrCreate(
            [
                'shopify_id' => $data['id'],
            ],
            [
                'order_id' => $order->id,
                'shopify_id' => $data['id'],
                'sku' =>$data['sku'] ?? $data['id'],
                'title' => $data['title'],
                'quantity' => $data['quantity'],
                'price' => Money::parseByDecimal(
                    $data['originalUnitPriceSet']['shopMoney']['amount'],
                    $data['originalUnitPriceSet']['shopMoney']['currencyCode'],
                )->getAmount(),
                'discount' => Money::parseByDecimal(
                    $data['totalDiscountSet']['shopMoney']['amount'],
                    $data['totalDiscountSet']['shopMoney']['currencyCode']
                )->getAmount(),
                'total' => Money::parseByDecimal(
                    $data['originalTotalSet']['shopMoney']['amount'],
                    $data['originalTotalSet']['shopMoney']['currencyCode']
                )->getAmount(),
            ]
        );
    }
}
