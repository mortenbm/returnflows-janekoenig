<?php declare(strict_types=1);

namespace App\Services\Shopify\Validators;

class GiftCardValidator extends DefaultValidator
{
    protected static function getRules(): array
    {
        return [
            'id' => ['required', 'string'],
            'note' => ['required', 'string'],
            'createdAt' => ['required', 'date'],
            'balance.amount' => ['required', 'string'],
            'balance.currencyCode' => ['required', 'string'],
        ];
    }
}
