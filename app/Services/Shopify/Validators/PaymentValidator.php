<?php declare(strict_types=1);

namespace App\Services\Shopify\Validators;

use Illuminate\Validation\Rule;

class PaymentValidator extends DefaultValidator
{
    protected static function prepare(array $data): array
    {
        $data['status'] = strtolower(trim($data['status']));
        return $data;
    }

    protected static function getRules(): array
    {
        return [
            'id' => ['required', ['string']],
            'status' => ['required', 'string', Rule::in(self::getStatuses())],
            'formattedGateway' => ['nullable', 'string'],
            'amountSet.shopMoney.amount' => ['required', 'string'],
            'amountSet.shopMoney.currencyCode' => ['required', 'string'],
            'createdAt' => ['required', 'date'],
        ];
    }

    protected static function getStatuses(): array
    {
        return [
            'awaiting_response',
            'error',
            'failure',
            'pending',
            'success',
            'unknown',
        ];
    }
}
