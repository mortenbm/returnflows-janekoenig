<?php declare(strict_types=1);

namespace App\Services\Shopify\Validators;

use Illuminate\Validation\Rule;

class FulfillmentValidator extends DefaultValidator
{
    protected static function prepare(array $data): array
    {
        $data['displayStatus'] = strtolower(trim($data['displayStatus']));
        return $data;
    }

    protected static function getRules(): array
    {
        return [
            'id' => ['required', 'string'],
            'name' => ['required', 'string'],
            'displayStatus' => ['required', 'string', Rule::in(self::getStatuses())],
            'deliveredAt' => ['nullable', 'date'],
            'estimatedDeliveryAt' => ['nullable', 'date'],
            'createdAt' => ['required', 'date'],
            'updatedAt' => ['nullable', 'date'],
            'fulfillmentLineItems' => ['required', 'array'],
            'fulfillmentLineItems.nodes' => ['required', 'array'],
            'fulfillmentLineItems.nodes.*.lineItem' => ['required', 'array'],
        ];
    }

    protected static function getStatuses(): array
    {
        return [
            'attempted_delivery',
            'cancelled',
            'confirmed',
            'delayed',
            'delivered',
            'failure',
            'fulfilled',
            'in_transit',
            'label_printed',
            'label_purchased',
            'label_voided',
            'marked_as_fulfilled',
            'not_delivered',
            'out_for_delivery',
            'picked_up',
            'ready_for_pickup',
            'submitted'
        ];
    }
}
