<?php declare(strict_types=1);

namespace App\Services\Shopify;

use App\Models\Store;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Shopify\Auth\FileSessionStorage;
use Shopify\Context;

class ShopifyClientFactory
{
    public function makeByStore(Store $store): ShopifyClient
    {
        self::validate($store);
        Context::initialize(
            apiKey: $store->shopify_client_id,
            apiSecretKey: $store->shopify_client_secret,
            scopes: ['read_orders', 'read_returns', 'read_fulfillments', 'read_gift_cards', 'read_products'],
            hostName: config('app.url'),
            sessionStorage: new FileSessionStorage('/tmp/shopify_sessions'),
        );

        return app()->make(ShopifyClient::class, [
            'config' => [
                'domain' => $store->shopify_domain,
                'token' => $store->shopify_access_token
            ]
        ]);
    }

    protected static function validate(Store $store): void
    {
        $data = $store->toArray();
        $validator = Validator::make($data, self::getRules());

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    protected static function getRules(): array
    {
        return [
            'is_active' => ['required', 'accepted'],
            'shopify_domain' => ['required', 'string'],
            'shopify_client_id' => ['required', 'string'],
            'shopify_client_secret' => ['required', 'string'],
            'shopify_access_token' => ['required', 'string'],
        ];
    }
}
