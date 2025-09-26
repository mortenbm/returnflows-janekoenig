<?php declare(strict_types=1);

namespace App\Enums;

use IsapOu\EnumHelpers\Concerns\InteractWithCollection;
use IsapOu\EnumHelpers\Contracts\UpdatableEnumColumns;

enum AddressTypeEnum: string implements UpdatableEnumColumns
{
    use InteractWithCollection;

    case SHIPPING = 'shipping';
    case BILLING = 'billing';

    public static function tables(): array
    {
        return [
            'shopify_order_addresses' => 'type'
        ];
    }
}
