<?php declare(strict_types=1);

namespace App\Services\Shopify\Mappers;

use App\Models\FulfillmentItem;
use App\Services\Shopify\Validators\FulfillmentItemValidator;
use Cknow\Money\Money;

class FulfillmentItemMapper
{
    public static function prepare(array $data, int $fulfillmentId): FulfillmentItem
    {
        FulfillmentItemValidator::validate($data);
        return FulfillmentItem::updateOrCreate(
            [
                'shopify_id' => $data['id'],
            ],
            [
                'fulfillment_id' => $fulfillmentId,
                'shopify_id' => $data['id'],
                'sku' => $data['sku'] ?? $data['id'],
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
