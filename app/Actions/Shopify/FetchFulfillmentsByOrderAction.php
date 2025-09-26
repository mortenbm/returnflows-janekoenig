<?php declare(strict_types=1);

namespace App\Actions\Shopify;

use App\Services\Shopify\ShopifyClient;
use App\Support\Shopify\GraphQLReader;

class FetchFulfillmentsByOrderAction
{
    private string $queryResource = 'fetch_fulfillments_order';

    public function __construct(
        protected ShopifyClient $shopifyClient
    ) {}

    public function handle(string $shopifyOrderId): array
    {
        $query = GraphQLReader::read($this->queryResource);
        $payload = [
            'query' => $query,
            'variables' => [
                'id' => $shopifyOrderId,
            ]
        ];

        return $this->shopifyClient->query(payload: $payload);
    }
}
