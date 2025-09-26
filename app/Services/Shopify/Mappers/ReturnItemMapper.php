<?php declare(strict_types=1);

namespace App\Services\Shopify\Mappers;

use App\Models\OrderReturnItem;
use App\Services\Shopify\Validators\ReturnItemValidator;
use Cknow\Money\Money;

class ReturnItemMapper
{
    public static function prepare(array $data, int $orderReturnId, bool $isGiftCard = false): OrderReturnItem
    {
        ReturnItemValidator::validate($data);

        $discountedUnitPriceSet = !$isGiftCard ? Money::parseByDecimal(
            $data['discountedUnitPriceSet']['shopMoney']['amount'] ?? 0,
            $data['discountedUnitPriceSet']['shopMoney']['currencyCode'],
        )->getAmount() : 0;

        $discountedUnitPriceAfterAllDiscountsSet = !$isGiftCard ? Money::parseByDecimal(
            $data['discountedUnitPriceAfterAllDiscountsSet']['shopMoney']['amount'] ?? 0,
            $data['discountedUnitPriceAfterAllDiscountsSet']['shopMoney']['currencyCode'],
        )->getAmount() : 0;
        $orderReturnLevelDiscount = ($discountedUnitPriceSet - $discountedUnitPriceAfterAllDiscountsSet)
            * $data['quantity'];

        $orderReturnItem = OrderReturnItem::updateOrCreate(
            [
                'return_id' => $orderReturnId,
                'shopify_id' => $data['id']
            ],
            [
                'return_id' => $orderReturnId,
                'shopify_id' => $data['id'],
                'sku' => $data['sku'] ?? $data['id'],
                'title' => $data['title'],
                'quantity' => $data['quantity'],
                'price' => !$isGiftCard ? Money::parseByDecimal(
                    $data['originalUnitPriceSet']['shopMoney']['amount'],
                    $data['originalUnitPriceSet']['shopMoney']['currencyCode'],
                )->getAmount() : 0,
                'discount' => !$isGiftCard ? Money::parseByDecimal(
                    $data['totalDiscountSet']['shopMoney']['amount'] ?? 0,
                    $data['totalDiscountSet']['shopMoney']['currencyCode'],
                )->getAmount() : 0,
                'total' => !$isGiftCard ? Money::parseByDecimal(
                    $data['discountedTotalSet']['shopMoney']['amount'] ?? 0,
                    $data['discountedTotalSet']['shopMoney']['currencyCode'],
                )->getAmount() : 0,
            ]);
        $orderReturnItem->orderReturnLevelDiscount = $orderReturnLevelDiscount;
        return $orderReturnItem;
    }
}
