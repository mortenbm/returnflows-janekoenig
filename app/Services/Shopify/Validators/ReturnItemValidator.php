<?php declare(strict_types=1);

namespace App\Services\Shopify\Validators;

class ReturnItemValidator extends DefaultValidator
{
    protected static function prepare(array $data): array
    {
        $data['sku'] = $data['sku'] ?? $data['id'];
        return $data;
    }

    protected static function getRules(): array
    {
        return [
            'id' => ['required', 'string'],
            'sku' => ['required', 'string'],
            'title' => ['required', 'string'],
            'quantity' => ['required', 'int', 'min:1'],
            'taxLines' => ['nullable', 'array'],
            'originalUnitPriceSet.shopMoney.amount' => ['required', 'string'],
            'originalUnitPriceSet.shopMoney.currencyCode' => ['required', 'string'],
            'discountedUnitPriceSet.shopMoney.amount' => ['required', 'string'],
            'discountedUnitPriceSet.shopMoney.currencyCode' => ['required', 'string'],
            'discountedUnitPriceAfterAllDiscountsSet.shopMoney.amount' => ['required', 'string'],
            'discountedUnitPriceAfterAllDiscountsSet.shopMoney.currencyCode' => ['required', 'string'],
            'discountedTotalSet.shopMoney.amount' => ['required', 'string'],
            'discountedTotalSet.shopMoney.currencyCode' => ['required', 'string'],
            'totalDiscountSet.shopMoney.amount' => ['required', 'string'],
            'totalDiscountSet.shopMoney.currencyCode' => ['required', 'string'],
            'originalTotalSet.shopMoney.amount' => ['required', 'string'],
            'originalTotalSet.shopMoney.currencyCode' => ['required', 'string'],
        ];
    }
}
