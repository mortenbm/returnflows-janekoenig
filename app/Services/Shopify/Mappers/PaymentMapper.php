<?php declare(strict_types=1);

namespace App\Services\Shopify\Mappers;

use App\Models\Payment;
use App\Services\Shopify\Validators\PaymentValidator;
use Cknow\Money\Money;

class PaymentMapper
{
    public static function prepare(array $data, int $orderId): Payment
    {
        PaymentValidator::validate($data);
        return Payment::updateOrCreate(
            ['shopify_id' => $data['id']],
            [
                'order_id' => $orderId,
                'status' => $data['status'],
                'amount' => Money::parseByDecimal(
                    $data['amountSet']['shopMoney']['amount'],
                    $data['amountSet']['shopMoney']['currencyCode']
                )->getAmount(),
                'currency' => $data['amountSet']['shopMoney']['currencyCode'],
                'gateway_name' => $data['formattedGateway'],
                'created_at' => $data['createdAt'],
            ]
        );
    }
}
