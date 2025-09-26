<?php

namespace App\Enums\Shopify;

enum EntityTypesEnum: string
{
    case ORDER = 'Order';
    case LINE_ITEM = 'LineItem';
    case FULFILLMENT = 'Fulfillment';
    case RETURN = 'Return';
}
