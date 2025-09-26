<?php

namespace App\Enums;

use IsapOu\EnumHelpers\Contracts\UpdatableEnumColumns;
use IsapOu\EnumHelpers\Concerns\InteractWithCollection;

enum NewOrderSystemEnum: string implements UpdatableEnumColumns
{
    use InteractWithCollection;

    case BC = 'business_central';
    case SHOPIFY = 'shopify';

    public static function tables(): array
    {
        return [
            'stores' => 'new_order_system',
        ];
    }
}
