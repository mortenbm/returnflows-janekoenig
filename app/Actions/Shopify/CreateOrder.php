<?php declare(strict_types=1);

namespace App\Actions\Shopify;

use App\Services\Shopify\ShopifyClient;
use App\Support\Shopify\GraphQLReader;

class CreateOrder
{
    private string $queryResource = 'mutations/create_order';

    public function __construct(
        protected ShopifyClient $shopifyClient
    ) {}

    public function handle(array $data): array
    {
        $query = GraphQLReader::read($this->queryResource);
        $payload = [
            'query' => $query,
            'variables' => [
                'order' => $data['order'],
                'options' => $data['options'],
            ]
        ];

        $response = $this->shopifyClient->query(payload: $payload);
        $orderId = data_get($response, 'data.orderCreate.order', []);
        if (empty($orderId)) {
            throw new \Exception('Failed to create order.');
        }
        return $orderId;
    }
}
