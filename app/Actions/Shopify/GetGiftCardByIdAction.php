<?php declare(strict_types=1);

namespace App\Actions\Shopify;

use App\Services\Shopify\ShopifyClient;
use App\Support\Shopify\GraphQLReader;

class GetGiftCardByIdAction
{
    private string $queryResource = 'get_giftcard_by_id';

    public function __construct(
        protected ShopifyClient $shopifyClient
    ) {}

    public function handle(string $giftCardId): array
    {
        $query = GraphQLReader::read($this->queryResource);
        $payload = [
            'query' => $query,
            'variables' => [
                'id' => $giftCardId,
            ]
        ];

        return $this->shopifyClient->query(payload: $payload);
    }
}
