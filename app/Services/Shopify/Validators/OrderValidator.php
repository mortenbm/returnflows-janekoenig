<?php declare(strict_types=1);

namespace App\Services\Shopify\Validators;

use Illuminate\Validation\Rule;

class OrderValidator extends DefaultValidator
{
    protected static function prepare(array $data): array
    {
        $data['displayFulfillmentStatus'] = strtolower(trim($data['displayFulfillmentStatus'] ?? ''));
        $data['riskLevel'] = strtolower(trim($data['riskLevel'] ?? ''));
        return $data;
    }

    protected static function getRules(): array
    {
        return [
            'id' => ['required', 'string'],
            'name' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'displayFulfillmentStatus' => ['required', 'string', Rule::in(self::getStatuses())],
            'currencyCode' => ['required', 'string'],
            'currentSubtotalPriceSet.shopMoney.amount' => ['required', 'string'],
            'currentTotalDiscountsSet.shopMoney.amount' => ['required', 'string'],
            'currentTotalTaxSet.shopMoney.amount' => ['required', 'string'],
            'currentTotalPriceSet.shopMoney.amount' => ['required', 'string'],
            'currentShippingPriceSet' => ['nullable', 'array'],
            'currentShippingPriceSet.shopMoney.amount' => ['required', 'string'],
            'riskLevel' => ['nullable', 'string', 'in:high,medium,low,none,pending'],
            'fulfillments' => ['nullable', 'array'],
            'fulfillments.*.id' => ['required', 'string'],
            'shippingAddress' => ['required', 'array'],
            'billingAddress' => ['required', 'array'],
            'transactions' => ['nullable', 'array'],
            'metafields' => ['nullable', 'array'],
            'shippingLine' => ['nullable', 'array'],
            'shippingLine.title' => ['required_with:shippingLine', 'string'],
        ];
    }

    protected static function getStatuses(): array
    {
        return [
            'fulfilled',
            'in_progress',
            'on_hold',
            'open',
            'partially_fulfilled',
            'pending_fulfillment',
            'request_declined',
            'restocked',
            'scheduled',
            'unfulfilled',
        ];
    }
}
