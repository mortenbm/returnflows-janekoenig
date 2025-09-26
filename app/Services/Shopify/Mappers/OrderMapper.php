<?php declare(strict_types=1);

namespace App\Services\Shopify\Mappers;

use App\Enums\AddressTypeEnum;
use App\Models\Order;
use App\Services\Shopify\Validators\OrderValidator;
use Cknow\Money\Money;

class OrderMapper
{
    public static function prepare(array $data): Order
    {
        OrderValidator::validate($data);
        $order = Order::updateOrCreate(
            ['shopify_id' => $data['id']],
            [
                'store_id' => $data['store_id'],
                'name' => $data['name'],
                'email' => $data['email'],
                'status' => $data['displayFulfillmentStatus'],
                'currency' => $data['currencyCode'],
                'subtotal' => Money::parseByDecimal(
                    $data['currentSubtotalPriceSet']['shopMoney']['amount'],
                    $data['currencyCode']
                )->getAmount(),
                'discount' => Money::parseByDecimal(
                    $data['currentTotalDiscountsSet']['shopMoney']['amount'],
                    $data['currencyCode']
                )->getAmount(),
                'total' => Money::parseByDecimal(
                    $data['currentTotalPriceSet']['shopMoney']['amount'],
                    $data['currencyCode']
                )->getAmount(),
                'shipping_amount' => Money::parseByDecimal(
                    $data['currentShippingPriceSet']['shopMoney']['amount'] ?? 0,
                    $data['currencyCode']
                )->getAmount(),
                'shipping_title' => $data['shippingLine']['title'] ?? null,
                'shipping_code' => $data['shippingLine']['code'] ?? null,
                'shipping_source' => $data['shippingLine']['source'] ?? null,
                'tags' => $data['tags'],
                'risk_level' => $data['riskLevel'],
                'client_ip' => $data['clientIp'],
                'customer_note' => $data['note'],
                'created_at' => $data['createdAt'],
                'updated_at' => $data['updatedAt'],
            ]
        );

        // Addresses
        $data['shippingAddress']['type'] = AddressTypeEnum::SHIPPING->value;
        $data['billingAddress']['type'] = AddressTypeEnum::BILLING->value;
        $addresses = collect([$data['shippingAddress'], $data['billingAddress']])
            ->map( fn ($address) => AddressMapper::prepare($address, $order->id) )
            ->filter();
        $order->setRelation('addresses', $addresses);

        // Payments
        if (!empty($data['transactions'])) {
            $payments = collect($data['transactions'])
                ->map( fn ($payment) => PaymentMapper::prepare($payment, $order->id) )
                ->filter();
            $order->setRelation('payments', $payments);
        }

        return $order;
    }
}
