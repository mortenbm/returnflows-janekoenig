<?php declare(strict_types=1);

namespace App\Services\Shopify\Mappers;

use App\Models\Address;
use App\Services\Shopify\Validators\AddressValidator;

class AddressMapper
{
    public static function prepare(array $data, int $orderId): Address
    {
        AddressValidator::validate($data);
        return Address::updateOrCreate(
            [
                'order_id' => $orderId,
                'type' => $data['type'],
            ],
            [
                'order_id' => $orderId,
                'type' => $data['type'],
                'first_name' => $data['firstName'],
                'last_name' => $data['lastName'],
                'address' => $data['address1'] . ' ' . ($data['address2'] ?? ''),
                'phone' => $data['phone'],
                'city' => $data['city'],
                'province' => $data['province'],
                'zip' => $data['zip'],
                'country' => $data['country'],
                'company' => $data['company'],
            ]
        );
    }
}
