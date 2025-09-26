<?php

namespace App\Enums\Shopify;

enum BulkOperationStatusEnum: string
{
    case CANCELLED = 'CANCELLED';
    case CANCELING = 'CANCELING';
    case COMPLETED = 'COMPLETED';
    case CREATED = 'CREATED';
    case EXPIRED = 'EXPIRED';
    case FAILED = 'FAILED';
    case RUNNING = 'RUNNING';
}
