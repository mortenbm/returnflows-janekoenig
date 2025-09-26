<?php declare(strict_types=1);

namespace App\Services\Shopify\Validators;

use App\Enums\AddressTypeEnum;
use Illuminate\Validation\Rule;

class AddressValidator extends DefaultValidator
{
    protected static function getRules(): array
    {
        return [
            'type' => ['required', Rule::enum(AddressTypeEnum::class)],
            'firstName' => ['required', 'string'],
            'lastName' => ['required', 'string'],
            'address1' => ['required', 'string'],
            'address2' => ['nullable', 'string'],
            'phone' => ['nullable', 'string'],
            'city' => ['required', 'string'],
            'province' => ['nullable', 'string'],
            'zip' => ['required', 'string'],
            'country' => ['required', 'string'],
            'company' => ['nullable', 'string'],
        ];
    }
}
