<?php declare(strict_types=1);

namespace App\Actions\Shopify;

use App\Services\Shopify\ShopifyClient;
use App\Support\Shopify\GraphQLReader;

class GetBulkOperationStatusAction
{
    private string $queryResource = 'bulk-operations/get_operation_status';

    public function __construct(
        protected ShopifyClient $shopifyClient
    ) {}

    public function handle(string $bulkOperationId): array
    {
        $query = GraphQLReader::read($this->queryResource);
        $payload = [
            'query' => $query,
            'variables' => [
                'id' => $bulkOperationId,
            ]
        ];

        return $this->shopifyClient->query(payload: $payload);
    }
}
