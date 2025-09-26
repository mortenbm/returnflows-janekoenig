<?php declare(strict_types=1);

namespace App\Actions\Shopify;

use App\Services\Shopify\ShopifyClient;
use App\Support\Shopify\GraphQLReader;
use App\Support\Shopify\CommonFlags;
use Illuminate\Support\Facades\Log;
use Outerweb\Settings\Models\Setting;

class SyncShopifyOrdersAction
{
    private string $queryResource = 'bulk-operations/fetch_orders';

    public function __construct(
        protected ShopifyClient $shopifyClient,
        protected int $storeId
    ) {}

    public function handle(): array
    {
        $query = GraphQLReader::read($this->queryResource);
        if (!$query) {
            Log::channel('shopify_graph')->error('Missing GraphQL query');
            return [];
        }

        $filterReplacer = '';
        if ($syncDate = Setting::get(sprintf('%s.%s.store', CommonFlags::ORDER_SYNC, $this->storeId))) {
            $filterReplacer = sprintf('(query: "updated_at:>=%s")', $syncDate);
        }

        $query = str_replace('___FILTER___', $filterReplacer, $query);
        return $this->shopifyClient->query(payload: ['query' => $query]);
    }
}
