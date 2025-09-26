<?php declare(strict_types=1);

namespace App\Actions\Shopify;

use App\Services\Shopify\ShopifyClient;
use App\Support\Shopify\GraphQLReader;

class AddOrderNoteAction
{
    private string $queryResource = 'add_order_note';

    public function __construct(
        protected ShopifyClient $shopifyClient
    ) {}

    public function handle(array $data): array
    {
        $query = GraphQLReader::read($this->queryResource);
        $payload = [
            'query' => $query,
            'variables' => [
                'input' => [
                    'id' => $data['order_id'],
                    'note' => $data['note'],
                ],
            ]
        ];

        return $this->shopifyClient->query(payload: $payload);
    }
}
