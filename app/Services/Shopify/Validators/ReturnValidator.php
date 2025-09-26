<?php declare(strict_types=1);

namespace App\Services\Shopify\Validators;

use Illuminate\Validation\Rule;

class ReturnValidator extends DefaultValidator
{
    protected static function prepare(array $data): array
    {
        $data['status'] = strtolower(trim($data['status']));
        return $data;
    }

    protected static function getRules(): array
    {
        return [
            'id' => ['required', 'string'],
            'name' => ['required', 'string'],
            'status' => ['required', 'string', Rule::in(self::getStatuses())],
            'returnLineItems' => ['required', 'array'],
            'returnLineItems.nodes' => ['required', 'array'],
            'returnLineItems.nodes.*.fulfillmentLineItem' => ['required', 'array'],
            'returnLineItems.nodes.*.fulfillmentLineItem.lineItem' => ['required', 'array'],
            'returnShippingFees' => ['nullable', 'array'],
            'returnShippingFees.*.amountSet.shopMoney.amount' => ['required_with:returnShippingFees', 'string'],
            'returnShippingFees.*.amountSet.shopMoney.currencyCode' => ['required_with:returnShippingFees', 'string'],
        ];
    }

    protected static function getStatuses(): array
    {
        return [
            'canceled',
            'closed',
            'declined',
            'open',
            'requested'
        ];
    }
}
